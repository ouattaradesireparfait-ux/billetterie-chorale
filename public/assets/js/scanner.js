(() => {
    const result = document.getElementById('result');
    const markButton = document.getElementById('mark-used');
    const manualForm = document.getElementById('manual-ticket');
    const ticketNumberInput = document.getElementById('ticket-number');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let currentPayload = null;
    let handlingScan = false;
    let scannerStarted = false;
    let cameraPaused = false;
    let resumeTimer = null;

    function showResult(status, message) {
        result.className = 'scan-result scan-result--' + status;
        result.textContent = message;
    }

    async function postPayload(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        return response.json();
    }

    function resumeAfter(delay = 5000) {
        window.clearTimeout(resumeTimer);

        const message = result.textContent;
        const resultClass = result.className;
        let secondsRemaining = Math.ceil(delay / 1000);

        function countDown() {
            if (secondsRemaining > 0) {
                result.className = resultClass;
                result.textContent = message + ' Nouveau scan dans ' + secondsRemaining + ' s.';
                secondsRemaining -= 1;
                resumeTimer = window.setTimeout(countDown, 1000);
                return;
            }

            currentPayload = null;
            markButton.disabled = true;
            ticketNumberInput.value = '';

            if (scannerStarted && cameraPaused) {
                scanner.resume()
                    .then(() => {
                        cameraPaused = false;
                        handlingScan = false;
                        showResult('ready', 'Caméra arrière active. Présentez le prochain QR code ou saisissez son numéro.');
                    })
                    .catch(() => {
                        cameraPaused = false;
                        handlingScan = false;
                        showResult('error', 'Le scan a été interrompu. Rechargez cette page pour reprendre.');
                    });

                return;
            }

            handlingScan = false;
            showResult('ready', 'Saisissez un numéro de billet ou présentez un QR code.');
        }

        countDown();
    }

    async function previewTicket(payload) {
        if (handlingScan) {
            return;
        }

        handlingScan = true;
        currentPayload = payload;
        markButton.disabled = true;

        if (scannerStarted && !cameraPaused) {
            try {
                scanner.pause(true);
                cameraPaused = true;
            } catch (error) {
                cameraPaused = false;
            }
        }

        showResult('checking', 'Vérification du billet…');

        try {
            const data = await postPayload('/admin/scanner/valider', payload);

            if (data.status === 'valid' && data.ok) {
                showResult('valid', data.message + ' Appuyez sur le bouton pour enregistrer l’entrée.');
                markButton.disabled = false;

                if (payload.ticket_number) {
                    resumeAfter(5000);
                }

                return;
            }

            if (data.status === 'used') {
                const scanTime = data.used_at
                    ? ' Entrée enregistrée le ' + data.used_at + '.'
                    : '';
                showResult('already-used', data.message + scanTime);
            } else if (data.status === 'payment_pending') {
                showResult('invalid', data.message);
            } else {
                showResult('invalid', data.message || 'Aucun billet ne correspond à cette saisie.');
            }

            resumeAfter(5000);
        } catch (error) {
            showResult('error', 'Erreur réseau pendant la vérification. Le scan va reprendre.');
            resumeAfter(5000);
        }
    }

    function verifyManualNumber() {
        const number = ticketNumberInput.value.trim().toUpperCase();

        ticketNumberInput.value = number;

        if (!/^CH-[0-9]{6}-[0-9]{2}$/.test(number)) {
            ticketNumberInput.setCustomValidity('Saisissez uniquement un numéro au format CH-000001-01.');
            ticketNumberInput.reportValidity();
            return;
        }

        ticketNumberInput.setCustomValidity('');
        previewTicket({ ticket_number: number });
    }

    manualForm.addEventListener('submit', (event) => {
        event.preventDefault();
        verifyManualNumber();
    });

    ticketNumberInput.addEventListener('input', () => {
        ticketNumberInput.value = ticketNumberInput.value.toUpperCase();
        ticketNumberInput.setCustomValidity('');
    });

    markButton.addEventListener('click', async () => {
        if (!currentPayload || markButton.disabled) {
            return;
        }

        window.clearTimeout(resumeTimer);
        resumeTimer = null;
        markButton.disabled = true;
        showResult('checking', 'Enregistrement de l’entrée…');

        try {
            const data = await postPayload('/admin/scanner/marquer', currentPayload);

            if (data.status === 'marked' && data.ok) {
                showResult('marked', data.message);
            } else if (data.status === 'used') {
                const scanTime = data.used_at
                    ? ' Entrée enregistrée le ' + data.used_at + '.'
                    : '';
                showResult('already-used', data.message + scanTime);
            } else if (data.status === 'payment_pending') {
                showResult('invalid', data.message);
            } else {
                showResult('invalid', data.message || 'Ce billet ne peut pas être validé.');
            }

            resumeAfter(5000);
        } catch (error) {
            showResult('error', 'Erreur réseau pendant l’enregistrement. Le scan va reprendre.');
            resumeAfter(5000);
        }
    });

    if (!window.Html5Qrcode) {
        showResult('error', 'Le scanner est indisponible. Vous pouvez saisir le numéro du billet.');
        return;
    }

    const scanner = new Html5Qrcode('reader', { verbose: false });
    const cameraConfig = {
        fps: 10,
        aspectRatio: 1,
        qrbox: (width, height) => {
            const size = Math.min(width, height, 280);

            return { width: size, height: size };
        },
    };

    scanner.start(
        { facingMode: { exact: 'environment' } },
        cameraConfig,
        (decodedText) => {
            const token = decodedText.trim();

            if (token) {
                previewTicket({ token: token });
            }
        },
        () => {}
    )
        .then(() => {
            scannerStarted = true;

            if (!handlingScan) {
                showResult('ready', 'Caméra arrière active. Présentez un QR code ou saisissez son numéro.');
            }
        })
        .catch(() => {
            showResult(
                'error',
                'Impossible d’ouvrir la caméra arrière. Autorisez son accès en HTTPS ou saisissez le numéro du billet.'
            );
        });

    window.addEventListener('pagehide', () => {
        window.clearTimeout(resumeTimer);

        if (scannerStarted) {
            scanner.stop().catch(() => {});
        }
    });
})();