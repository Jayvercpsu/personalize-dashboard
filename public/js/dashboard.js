document.addEventListener("DOMContentLoaded", () => {
    const countWords = (value) => (value.trim() ? value.trim().split(/\s+/).length : 0);

    document.querySelectorAll("[data-note-card]").forEach((card) => {
        const textarea = card.querySelector("[data-note-input]");
        const counter = card.querySelector("[data-note-words]");
        const expandButton = card.querySelector("[data-toggle-note]");

        if (textarea && counter) {
            const renderCount = () => {
                counter.textContent = String(countWords(textarea.value));
            };

            renderCount();
            textarea.addEventListener("input", renderCount);
        }

        if (expandButton) {
            expandButton.addEventListener("click", () => {
                card.classList.toggle("is-expanded");
                expandButton.textContent = card.classList.contains("is-expanded")
                    ? "Collapse note"
                    : "Expand note";
            });
        }
    });

    const chatInput = document.querySelector("#chat-input");
    document.querySelectorAll("[data-emoji]").forEach((button) => {
        button.addEventListener("click", () => {
            if (!chatInput) return;
            chatInput.value = `${chatInput.value} ${button.dataset.emoji}`.trim();
            chatInput.focus();
        });
    });

    const chatThread = document.querySelector("#chat-thread");
    if (chatThread) {
        chatThread.scrollTop = chatThread.scrollHeight;
    }

    const appRoot = document.querySelector(".dashboard-app");
    const sideNav = document.querySelector("#side-nav");
    const toggleButton = document.querySelector("[data-sidebar-toggle]");
    const closeButton = document.querySelector("[data-sidebar-close]");
    const mobileQuery = window.matchMedia("(max-width: 1180px)");
    const sidebarStorageKey = "icqa_sidebar_collapsed";

    const updateToggleVisual = () => {
        if (!toggleButton || !appRoot || !sideNav) return;

        const isMobile = mobileQuery.matches;
        const isOpen = isMobile
            ? sideNav.classList.contains("is-open")
            : !appRoot.classList.contains("sidebar-collapsed");

        toggleButton.classList.toggle("is-open", isOpen);
        toggleButton.setAttribute("aria-label", isOpen ? "Close sidebar" : "Open sidebar");
    };

    const applyDesktopSidebarPreference = () => {
        if (!appRoot || mobileQuery.matches) return;

        const collapsed = localStorage.getItem(sidebarStorageKey) === "1";
        appRoot.classList.toggle("sidebar-collapsed", collapsed);
    };

    applyDesktopSidebarPreference();
    updateToggleVisual();

    toggleButton?.addEventListener("click", () => {
        if (!appRoot || !sideNav) return;

        if (mobileQuery.matches) {
            sideNav.classList.toggle("is-open");
        } else {
            appRoot.classList.toggle("sidebar-collapsed");
            localStorage.setItem(
                sidebarStorageKey,
                appRoot.classList.contains("sidebar-collapsed") ? "1" : "0"
            );
        }

        updateToggleVisual();
    });

    closeButton?.addEventListener("click", () => {
        if (!appRoot || !sideNav) return;

        if (mobileQuery.matches) {
            sideNav.classList.remove("is-open");
        } else {
            appRoot.classList.add("sidebar-collapsed");
            localStorage.setItem(sidebarStorageKey, "1");
        }

        updateToggleVisual();
    });

    const sectionButtons = Array.from(document.querySelectorAll("[data-section-btn]"));
    const sections = Array.from(document.querySelectorAll("[data-dashboard-section]"));
    const sectionStateInputs = Array.from(
        document.querySelectorAll("#profile-section-input, #reset-chat-section-input")
    );

    const showSection = (sectionId) => {
        sectionButtons.forEach((button) => {
            button.classList.toggle("is-active", button.dataset.target === sectionId);
        });

        sections.forEach((section) => {
            section.classList.toggle("is-active", section.id === sectionId);
        });

        sectionStateInputs.forEach((input) => {
            input.value = sectionId;
        });
    };

    const initialSection = sections.find((section) => section.classList.contains("is-active"))?.id;
    if (initialSection) {
        showSection(initialSection);
    }

    sectionButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const target = button.dataset.target;
            showSection(target);

            if (mobileQuery.matches && sideNav) {
                sideNav.classList.remove("is-open");
                updateToggleVisual();
            }
        });
    });

    const closeAllModals = () => {
        document.querySelectorAll("[data-modal]").forEach((modal) => {
            modal.classList.remove("is-open");
        });
    };

    document.querySelectorAll("[data-open-modal]").forEach((button) => {
        button.addEventListener("click", () => {
            const target = button.dataset.openModal;
            const modal = document.querySelector(`[data-modal="${target}"]`);
            if (modal) {
                closeAllModals();
                modal.classList.add("is-open");
            }
        });
    });

    document.querySelectorAll("[data-close-modal]").forEach((button) => {
        button.addEventListener("click", closeAllModals);
    });

    document.querySelectorAll("[data-modal]").forEach((modal) => {
        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                closeAllModals();
            }
        });
    });

    const toast = document.querySelector("[data-toast]");
    if (toast) {
        window.setTimeout(() => {
            toast.parentElement?.remove();
        }, 4300);
    }

    mobileQuery.addEventListener("change", () => {
        if (!appRoot || !sideNav) return;

        if (mobileQuery.matches) {
            appRoot.classList.remove("sidebar-collapsed");
            sideNav.classList.remove("is-open");
        } else {
            sideNav.classList.remove("is-open");
            applyDesktopSidebarPreference();
        }

        updateToggleVisual();
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeAllModals();
            sideNav?.classList.remove("is-open");
            updateToggleVisual();
        }
    });
});
