export function initializePracticeNavigation(content) {
    const practice = content.querySelector('[data-practice-session]');
    if (!practice) return;

    const panel = practice.querySelector('[data-practice-panel]');
    const status = practice.querySelector('[data-practice-request-status]');
    const error = practice.querySelector('[data-practice-request-error]');
    let pending = false;
    let needsReload = false;

    practice.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;
        event.preventDefault();
        if (pending || needsReload || content.inert || document.hidden || !form.reportValidity()) return;

        // These text-only forms use the same encoding as a normal browser submission.
        const body = new URLSearchParams(new FormData(form));
        if (event.submitter?.name) body.append(event.submitter.name, event.submitter.value);
        const controls = [...practice.querySelectorAll('button, input, select, textarea')]
            .filter((control) => !control.disabled);

        pending = true;
        controls.forEach((control) => { control.disabled = true; });
        panel.setAttribute('aria-busy', 'true');
        status.hidden = false;
        error.hidden = true;

        try {
            // Keep the existing POST/redirect/GET flow, but never unload the fullscreen document.
            const response = await fetch(form.action, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-CitiRx-Practice': 'panel' },
            });
            if (!response.ok) {
                throw new Error(response.status === 419
                    ? 'Your sign-in session expired. Reload practice before continuing.'
                    : 'The practice result could not be loaded. Your last action may already be saved. Reload before submitting again.');
            }
            if (new URL(response.url).origin !== window.location.origin) throw new Error('Unexpected practice response. Reload before continuing.');

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextPractice = page.querySelector('[data-practice-session]');
            if (!nextPractice) {
                // Summary, sign-in, and setup redirects legitimately leave this assessment.
                if (response.redirected && response.url !== window.location.href) {
                    window.location.assign(response.url);
                    return;
                }
                throw new Error('The practice page could not be updated. Reload before continuing.');
            }

            const nextPanel = nextPractice.querySelector('[data-practice-panel]');
            if (!nextPanel || nextPractice.dataset.practiceSession !== practice.dataset.practiceSession) {
                throw new Error('The practice session changed. Reload before continuing.');
            }

            // Replace only the question panel; the fullscreen root and guard listeners stay intact.
            nextPanel.querySelectorAll('script').forEach((script) => script.remove());
            panel.replaceChildren(...document.importNode(nextPanel, true).childNodes);
            if (!content.inert && !document.hidden) panel.focus();
        } catch (exception) {
            needsReload = true;
            error.querySelector('[data-practice-error-message]').textContent = exception instanceof TypeError
                ? 'Connection lost. Your last action may already be saved. Reload practice before submitting again.'
                : exception.message;
            error.hidden = false;
        } finally {
            pending = false;
            panel.removeAttribute('aria-busy');
            status.hidden = true;
            if (!needsReload) controls.forEach((control) => { control.disabled = false; });
        }
    });
}
