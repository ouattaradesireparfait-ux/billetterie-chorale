(() => {
    const result = document.getElementById('result');
    const manualForm = document.getElementById('manual');
    const tokenInput = document.getElementById('token');
    const nextButton = document.getElementById('scan-next');
    let cameraScanLocked = false;

    async function checkTicket(value, fromCamera = false) {
        if (fromCamera) {
            if (cameraScanLocked) {
                return;
            }

            cameraScanLocked = true;
        }

        try {
            const response = await fetch('/admin/scanner/valider', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify({ token: value.trim() }),
            });

            const data = await response.json();

            result.className = data.ok ? 'valid' : 'invalid';

            const existence = data.exists
                ? 'Billet existant.'
                : 'Billet inexistant.';
            const appStatus = !data.exists
                ? ''
                : data.scanned_in_app
                    ? 'Déjà scanné dans l’application.'
                    : 'Pas encore scanné dans l’application.';
            const scanTime = data.used_at
                ? `Scanné le ${data.used_at}.`
                : '';

            result.textContent = [existence, appStatus, scanTime, data.message]
                .filter(Boolean)
                .join(' ');
            nextButton.hidden = !fromCamera;
        } catch (error) {
            result.className = 'invalid';
            result.textContent = 'Erreur réseau. Réessayez.';
            nextButton.hidden = !fromCamera;
        }
    }

    manualForm.addEventListener('submit', (event) => {
        event.preventDefault();
        checkTicket(tokenInput.value);
    });

    nextButton.addEventListener('click', () => {
        cameraScanLocked = false;
        nextButton.hidden = true;
        result.textContent = '';
    });

    if (window.Html5QrcodeScanner) {
        const scanner = new Html5QrcodeScanner(
            'reader',
            {
                fps: 10,
                qrbox: 230,
            },
            false
        );

        scanner.render(
            (decodedText) => checkTicket(decodedText, true),
            () => {}
        );
    } else {
        result.className = 'invalid';
        result.textContent = 'Scanner indisponible, saisissez le code.';
    }
})();