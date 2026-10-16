(() => {
    const result = document.getElementById('result');
    const markButton = document.getElementById('mark-used');
    const newScanButton = document.getElementById('new-scan');
    const manualForm = document.getElementById('manual-ticket');
    const ticketNumberInput = document.getElementById('ticket-number');
    const verifyButton = document.getElementById('verify-ticket');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let scanner = null;
    let cameraStatus = 'idle';
    let cameraStartPromise = null;
    let pauseWhenReady = false;
    let currentPayload = null;
    let requestInProgress = false;
    let pageLeaving = false;

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

    async function disposeScanner() {
        if (!scanner) {
            cameraStatus = 'idle';
            return true;
        }

        const oldScanner = scanner;

        if (cameraStatus === 'running' || cameraStatus === 'paused') {
            try {
                await oldScanner.stop();
            } catch (error) {
                showResult('error', 'Impossible de libérer la caméra. Rechargez la page avant de réessayer.');
                return false;
            }
        }

        try {
            oldScanner.clear();
        } catch (error) {
            // La caméra est déjà arrêtée; le prochain démarrage recréera le lecteur.
        }

        if (scanner === oldScanner) {
            scanner = null;
            cameraStatus = 'idle';
        }

        return true;
    }

    function pauseScanner() {
        if (cameraStatus === 'starting') {
            pauseWhenReady = true;
            return;
        }

        if (cameraStatus !== 'running' || !scanner) {
            return;
        }

        try {
            scanner.pause(true);
            cameraStatus = 'paused';
        } catch (error) {
            // Si la pause echoue, conserver l'etat actif pour que stop() libere la camera au prochain scan.
            cameraStatus = 'running';
        }
    }

    async function startCamera(forceRestart = false) {
        if (cameraStartPromise) {
            return cameraStartPromise;
        }

        if (!forceRestart && cameraStatus === 'running') {
            return true;
        }

        cameraStartPromise = (async () => {
            if (!window.Html5Qrcode) {
                cameraStatus = 'error';
                showResult('error', 'Le lecteur QR est indisponible. Vérifiez votre connexion ou saisissez le numéro du billet.');
                newScanButton.disabled = false;
                return false;
            }

            if (scanner && !await disposeScanner()) {
                newScanButton.disabled = false;
                return false;
            }

            cameraStatus = 'starting';
            pauseWhenReady = false;
            newScanButton.disabled = true;
            showResult('checking', 'Ouverture de la caméra arrière…');

            const activeScanner = new Html5Qrcode('reader', { verbose: false });
            scanner = activeScanner;

            const cameraConfig = {
                fps: 10,
                aspectRatio: 1,
                qrbox: (width, height) => {
                    const size = Math.min(width, height, 280);

                    return { width: size, height: size };
                },
            };

            try {
                await activeScanner.start(
                    { facingMode: { exact: 'environment' } },
                    cameraConfig,
                    (decodedText) => {
                        const token = decodedText.trim();

                        if (token) {
                            checkTicket({ token: token });
                        }
                    },
                    () => {}
                );

                cameraStatus = 'running';

                if (pageLeaving) {
                    await disposeScanner();
                    return false;
                }

                if (pauseWhenReady || currentPayload) {
                    pauseScanner();
                    newScanButton.disabled = false;
                } else {
                    showResult('ready', 'Caméra arrière active. Présentez un QR code ou saisissez son numéro.');
                    newScanButton.disabled = true;
                }

                return true;
            } catch (error) {
                cameraStatus = 'error';

                try {
                    activeScanner.clear();
                } catch (clearError) {
                    // Le lecteur n’a pas été initialisé; aucune action supplémentaire n’est nécessaire.
                }

                if (scanner === activeScanner) {
                    scanner = null;
                }

                if (!currentPayload) {
                    showResult(
                        'error',
                        'Impossible d’ouvrir la caméra arrière. Autorisez son accès en HTTPS ou saisissez le numéro du billet.'
                    );
                }

                newScanButton.disabled = false;
                return false;
            }
        })().finally(() => {
            cameraStartPromise = null;

            if (currentPayload && !requestInProgress) {
                newScanButton.disabled = false;
            }
        });

        return cameraStartPromise;
    }

    async function checkTicket(payload) {
        if (requestInProgress || currentPayload) {
            return;
        }

        requestInProgress = true;
        currentPayload = payload;
        verifyButton.disabled = true;
        markButton.disabled = true;
        newScanButton.disabled = true;
        pauseScanner();
        showResult('checking', 'Vérification du billet…');

        try {
            const data = await postPayload('/admin/scanner/valider', payload);

            if (data.status === 'valid' && data.ok) {
                showResult('valid', data.message + ' Appuyez sur « Marquer comme déjà scanné » pour enregistrer l’entrée.');
                markButton.disabled = false;
            } else if (data.status === 'used') {
                const scanTime = data.used_at
                    ? ' Entrée enregistrée le ' + data.used_at + '.'
                    : '';
                showResult('already-used', data.message + scanTime);
            } else if (data.status === 'payment_pending') {
                showResult('invalid', data.message);
            } else {
                showResult('invalid', data.message || 'Aucun billet ne correspond à cette saisie.');
            }
        } catch (error) {
            showResult('error', 'Erreur réseau pendant la vérification. Appuyez sur « Nouveau scan » pour réessayer.');
        } finally {
            requestInProgress = false;
            verifyButton.disabled = true;
            newScanButton.disabled = cameraStatus === 'starting';
        }
    }

    manualForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const number = ticketNumberInput.value.trim().toUpperCase();
        ticketNumberInput.value = number;

        if (!/^CH-[0-9]{6}-[0-9]{2}$/.test(number)) {
            ticketNumberInput.setCustomValidity('Saisissez uniquement un numéro au format CH-000001-01.');
            ticketNumberInput.reportValidity();
            return;
        }

        ticketNumberInput.setCustomValidity('');
        checkTicket({ ticket_number: number });
    });

    ticketNumberInput.addEventListener('input', () => {
        ticketNumberInput.value = ticketNumberInput.value.toUpperCase();
        ticketNumberInput.setCustomValidity('');
    });

    markButton.addEventListener('click', async () => {
        if (!currentPayload || requestInProgress || markButton.disabled) {
            return;
        }

        requestInProgress = true;
        markButton.disabled = true;
        newScanButton.disabled = true;
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
        } catch (error) {
            showResult('error', 'Erreur réseau pendant le marquage. Vous pouvez réessayer ou lancer un nouveau scan.');
            markButton.disabled = false;
        } finally {
            requestInProgress = false;
            newScanButton.disabled = cameraStatus === 'starting';
        }
    });

    newScanButton.addEventListener('click', async () => {
        if (requestInProgress || cameraStartPromise) {
            return;
        }

        currentPayload = null;
        pauseWhenReady = false;
        ticketNumberInput.value = '';
        ticketNumberInput.setCustomValidity('');
        verifyButton.disabled = false;
        markButton.disabled = true;
        newScanButton.disabled = true;

        await startCamera(true);
    });

    window.addEventListener('pagehide', () => {
        pageLeaving = true;

        if (scanner && (cameraStatus === 'running' || cameraStatus === 'paused')) {
            const oldScanner = scanner;

            oldScanner.stop()
                .then(() => oldScanner.clear())
                .catch(() => {});
        }
    });

    startCamera();
})();