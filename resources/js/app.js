import './bootstrap';

function getCommandPaletteElements() {
    return {
        modalElement: document.getElementById('commandPaletteModal'),
        openButton: document.getElementById('openCommandPalette'),
        searchInput: document.getElementById('commandPaletteSearch'),
        emptyState: document.getElementById('commandPaletteEmpty'),
        items: Array.from(document.querySelectorAll('.command-palette-item')),
    };
}

function setActiveCommandItem(items, activeIndex) {
    items.forEach((item, index) => {
        item.classList.toggle('active', index === activeIndex);
        item.setAttribute('aria-selected', index === activeIndex ? 'true' : 'false');
    });
}

function filterCommandItems(keyword, items, emptyState) {
    const normalizedKeyword = keyword.trim().toLowerCase();
    const visibleItems = [];

    items.forEach((item) => {
        const text = item.textContent.toLowerCase();
        const keywords = (item.dataset.commandKeywords || '').toLowerCase();
        const isMatch = !normalizedKeyword || text.includes(normalizedKeyword) || keywords.includes(normalizedKeyword);

        item.classList.toggle('d-none', !isMatch);
        if (isMatch) {
            visibleItems.push(item);
        }
    });

    emptyState.classList.toggle('d-none', visibleItems.length > 0);
    const currentPath = window.location.pathname;

    let activeIndex = visibleItems.findIndex(item => {
        const itemPath = new URL(item.href).pathname;
        return itemPath === currentPath;
    });

    if (activeIndex === -1) {
        activeIndex = -1; // jangan fallback ke 0
    }

    setActiveCommandItem(visibleItems, activeIndex);
    return visibleItems;
}

function openCommandPalette(modal, searchInput, items, emptyState) {
    modal.show();
    searchInput.value = '';
    const visibleItems = filterCommandItems('', items, emptyState);

    setTimeout(() => {
        searchInput.focus();
    }, 150);
}

function selectCommandItem(item) {
    if (item && item.href) {
        window.location.href = item.href;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const { modalElement, openButton, searchInput, emptyState, items } = getCommandPaletteElements();

    if (!modalElement || !openButton || !searchInput || !emptyState || items.length === 0 || !window.bootstrap) {
        return;
    }

    const modal = new window.bootstrap.Modal(modalElement);
    let visibleItems = filterCommandItems('', items, emptyState);
    let activeIndex = visibleItems.length > 0 ? 0 : -1;

    openButton.addEventListener('click', () => {
        openCommandPalette(modal, searchInput, items, emptyState);
        visibleItems = filterCommandItems('', items, emptyState);
        activeIndex = visibleItems.length > 0 ? 0 : -1;
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            openCommandPalette(modal, searchInput, items, emptyState);
            visibleItems = filterCommandItems('', items, emptyState);
            activeIndex = visibleItems.length > 0 ? 0 : -1;
        }
    });

    searchInput.addEventListener('input', () => {
        visibleItems = filterCommandItems(searchInput.value, items, emptyState);
        activeIndex = visibleItems.length > 0 ? 0 : -1;
    });

    searchInput.addEventListener('keydown', (event) => {
        if (visibleItems.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeIndex = (activeIndex + 1) % visibleItems.length;
            setActiveCommandItem(visibleItems, activeIndex);
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = (activeIndex - 1 + visibleItems.length) % visibleItems.length;
            setActiveCommandItem(visibleItems, activeIndex);
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            selectCommandItem(visibleItems[activeIndex]);
        }
    });
});
