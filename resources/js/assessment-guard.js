export function initializeAssessmentGuard() {
    const content = document.querySelector('[data-assessment-content]');
    const gate = document.querySelector('[data-assessment-gate]');
    if (!content || !gate) return;

    const root = document.documentElement;
    const title = gate.querySelector('[data-assessment-gate-title]');
    const message = gate.querySelector('[data-assessment-gate-message]');
    const unavailable = gate.querySelector('[data-assessment-fullscreen-unavailable]');
    const error = gate.querySelector('[data-assessment-fullscreen-error]');
    const enter = gate.querySelector('[data-assessment-enter]');
    const notice = document.querySelector('[data-assessment-clipboard-notice]');
    let entered = false;
    let requesting = false;
    let previousFocus = null;
    let noticeTimeout = null;

    const isFullscreen = () => document.fullscreenElement === root;
    const supportsFullscreen = () => typeof root.requestFullscreen === 'function'
        && document.fullscreenEnabled !== false;

    const updateButton = () => {
        enter.disabled = requesting;
        enter.setAttribute('aria-busy', String(requesting));
        enter.textContent = requesting ? 'Opening fullscreen…'
            : !supportsFullscreen() ? (entered ? 'Resume in focus mode' : 'Continue in focus mode')
            : isFullscreen() ? 'Resume assessment'
            : entered ? 'Return to fullscreen' : 'Enter fullscreen';
    };

    const showGate = () => {
        if (document.hidden || gate.open) return;
        if (typeof gate.showModal === 'function') gate.showModal();
        else gate.setAttribute('open', '');
    };

    const lock = (reason) => {
        if (content.contains(document.activeElement)) previousFocus = document.activeElement;
        content.inert = true;
        root.classList.add('assessment-focus-locked');
        title.textContent = entered ? 'Resume your assessment' : 'Before you answer';
        message.textContent = reason;
        unavailable.hidden = supportsFullscreen();
        error.hidden = true;
        updateButton();
        showGate();
    };

    const unlock = () => {
        entered = true;
        content.inert = false;
        root.classList.remove('assessment-focus-locked');
        if (typeof gate.close === 'function') gate.close();
        else gate.removeAttribute('open');
        const focusTarget = previousFocus?.isConnected ? previousFocus : content;
        focusTarget.focus({ preventScroll: true });
    };

    // Promote the initially visible fallback to a native modal after the page is ready.
    if (gate.open) {
        if (typeof gate.close === 'function') gate.close();
        else gate.removeAttribute('open');
    }
    gate.addEventListener('cancel', (event) => event.preventDefault());
    enter.addEventListener('click', async () => {
        if (requesting || document.hidden) return;
        if (!supportsFullscreen() || isFullscreen()) {
            unlock();
            return;
        }

        requesting = true;
        error.hidden = true;
        updateButton();
        try {
            // Fullscreen must be requested directly from the student's click.
            await root.requestFullscreen();
            if (isFullscreen() && !document.hidden) unlock();
        } catch {
            lock('Fullscreen could not be opened.');
            error.textContent = 'Allow fullscreen and try again. If it is blocked by your browser, return to the dashboard and open the assessment in a supported browser.';
            error.hidden = false;
        } finally {
            requesting = false;
            updateButton();
        }
    });

    document.addEventListener('fullscreenchange', () => {
        if (!isFullscreen()) lock('Fullscreen was closed. Return to fullscreen before continuing.');
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) lock('You left the assessment tab. Resume before continuing.');
        else if (content.inert) showGate();
    });

    const touchesContent = (event) => {
        if (event.target instanceof Node && content.contains(event.target)) return true;
        if (!['copy', 'cut', 'dragstart'].includes(event.type)) return false;
        const selection = window.getSelection();
        for (let index = 0; index < (selection?.rangeCount ?? 0); index++) {
            if (selection.getRangeAt(index).intersectsNode(content)) return true;
        }
        return false;
    };
    const blockClipboard = (event) => {
        if (!touchesContent(event)) return;
        event.preventDefault();
        event.stopPropagation();
        notice.hidden = false;
        window.clearTimeout(noticeTimeout);
        noticeTimeout = window.setTimeout(() => { notice.hidden = true; }, 4000);
    };
    for (const type of ['copy', 'cut', 'paste', 'dragstart', 'drop']) {
        document.addEventListener(type, blockClipboard, true);
    }
    document.addEventListener('beforeinput', (event) => {
        if (['insertFromPaste', 'insertFromDrop', 'deleteByCut'].includes(event.inputType)) {
            blockClipboard(event);
        }
    }, true);

    lock('Enter fullscreen to answer questions. You can return to the dashboard at any time.');
}
