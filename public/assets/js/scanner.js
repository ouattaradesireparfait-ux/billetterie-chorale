(() => {
    const resultDialog = document.getElementById('scan-result-dialog');
    const result = document.getElementById('result');
    const actions = document.getElementById('scanner-actions');
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
    let restartInProgress = false;
    let pageLeaving = false;

    function showResult(status, message, { canMark = false, canStartNewScan = true } = {}) {
        result.className = 'scan-result scan-result--' + status;
        result.textContent = message;
        markButton.hidden = !canMark;
        markButton.disabled = !canMark;
        newScanButton.disabled = !canStartNewScan;
        actions.classList.toggle('scanner-actions--single', !canMark);

        if (!resultDialog.open) {
            resultDialog.showModal();
        }

        if (canStartNewScan) {
            (canMark ? markButton : newScanButton).focus();
        }
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
                cameraStatus = 'idle';
            } catch (error) {
                showResult(
                    'error',
                    'Impossible de libérer la caméra. Rechargez la page avant de réessayer.'
                );
                return false;
            }
        }

        try {
            oldScanner.clear();
        } catch (error) {
            showResult(
                'error',
                'Impossible de réinitialiser le lecteur. Rechargez la page avant de réessayer.'
            );
            return false;
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
            // Garder l’état actif afin que le prochain scan puisse arrêter la caméra.
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

        const pendingStart = (async () => {
            if (!window.Html5Qrcode) {
                cameraStatus = 'error';
                showResult(
                    'error',
                    'Le lecteur QR est indisponible. Vérifiez votre connexion ou saisissez le numéro du billet.'
                );
                return false;
            }

            if (scanner && !await disposeScanner()) {
                return false;
            }

            cameraStatus = 'starting';
            pauseWhenReady = false;

            const activeScanner = new Html5Qrcode('reader', { verbose: false });
            scanner = activeScanner;

            const cameraConfig = {
                fps: 10,
                qrbox: (width, height) => {
                    const size = Math.floor(Math.min(Math.min(width, height) * 0.78, 320));

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
                }

                return true;
            } catch (error) {
                cameraStatus = 'error';

                try {
                    activeScanner.clear();
                } catch (clearError) {
                    // Le lecteur n’a pas démarré; il n’y a rien à libérer.
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

                return false;
            }
        })();

        cameraStartPromise = pendingStart;

        try {
            return await pendingStart;
        } finally {
            if (cameraStartPromise === pendingStart) {
                cameraStartPromise = null;
            }
        }
    }

    async function checkTicket(payload) {
        if (requestInProgress || currentPayload) {
            return;
        }

        requestInProgress = true;
        currentPayload = payload;
        verifyButton.disabled = true;
        markButton.hidden = true;
        markButton.disabled = true;
        newScanButton.disabled = true;
        pauseScanner();
        showResult('checking', 'Vérification du billet…', { canStartNewScan: false });

        try {
            const data = await postPayload('/admin/scanner/valider', payload);

            if (data.status === 'valid' && data.ok) {
                showResult(
                    'valid',
                    (data.message || 'Billet valide.') + ' Appuyez sur « Marquer comme déjà scanné » pour enregistrer l’entrée.',
                    { canMark: true }
                );
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
            showResult(
                'error',
                'Erreur réseau pendant la vérification. Appuyez sur « Nouveau scan » pour réessayer.'
            );
        } finally {
            requestInProgress = false;
            verifyButton.disabled = true;
            newScanButton.disabled = false;
        }
    }

    manualForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const number = ticketNumberInput.value.trim().toUpperCase();
        ticketNumberInput.value = number;

        if (!/^(?:VIP|STD)-[A-Z0-9]{4}-(?:00[1-9]|0[1-9][0-9]|[1-9][0-9]{2})$/.test(number)) {
            ticketNumberInput.setCustomValidity('Saisissez un numero au format VIP-A0A1-001 ou STD-B2C3-002.');
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
        showResult('checking', 'Enregistrement de l’entrée…', { canStartNewScan: false });

        try {
            const data = await postPayload('/admin/scanner/marquer', currentPayload);

            if (data.status === 'marked' && data.ok) {
                showResult('marked', data.message || 'Entrée enregistrée.');
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
            showResult(
                'error',
                'Erreur réseau pendant le marquage. Appuyez sur « Nouveau scan » pour réessayer.'
            );
        } finally {
            requestInProgress = false;
            newScanButton.disabled = false;
        }
    });

    newScanButton.addEventListener('click', async () => {
        if (requestInProgress || restartInProgress) {
            return;
        }

        restartInProgress = true;
        newScanButton.disabled = true;
        currentPayload = null;
        pauseWhenReady = false;
        ticketNumberInput.value = '';
        ticketNumberInput.setCustomValidity('');
        verifyButton.disabled = true;
        markButton.hidden = true;
        markButton.disabled = true;

        if (resultDialog.open) {
            resultDialog.close();
        }

        try {
            const previousStart = cameraStartPromise;

            if (previousStart) {
                await previousStart;

                if (resultDialog.open && currentPayload === null) {
                    resultDialog.close();
                }
            }

            const started = await startCamera(true);
            verifyButton.disabled = false;

            if (started && resultDialog.open) {
                resultDialog.close();
            }
        } finally {
            restartInProgress = false;
            verifyButton.disabled = false;

            if (resultDialog.open) {
                newScanButton.disabled = false;
            }
        }
    });

    resultDialog.addEventListener('cancel', (event) => {
        if (currentPayload) {
            event.preventDefault();
        }
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