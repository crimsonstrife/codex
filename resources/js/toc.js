/**
 * Table of Contents — auto-generates a sticky ToC from page headings.
 * Requires: #page-content (the content div) and #toc-list (empty <ul> to populate).
 * Hides #toc-wrapper if fewer than 2 headings are found.
 */
document.addEventListener('DOMContentLoaded', function () {
    const content    = document.getElementById('page-content');
    const tocList    = document.getElementById('toc-list');
    const tocWrapper = document.getElementById('toc-wrapper');

    if (!content || !tocList || !tocWrapper) return;

    const headings = Array.from(content.querySelectorAll('h1, h2, h3, h4'));

    if (headings.length < 2) {
        tocWrapper.classList.add('d-none');
        return;
    }

    headings.forEach((heading, index) => {
        if (!heading.id) {
            heading.id = 'heading-' + index;
        }

        const level = parseInt(heading.tagName[1], 10);
        const li    = document.createElement('li');
        li.style.paddingLeft = Math.max(0, (level - 2) * 0.75) + 'rem';

        const a = document.createElement('a');
        a.href             = '#' + heading.id;
        a.className        = 'toc-link text-body-secondary text-decoration-none small d-block py-1 lh-sm';
        a.textContent      = heading.textContent.replace(/^#+\s*/, '');
        a.dataset.headingId = heading.id;

        li.appendChild(a);
        tocList.appendChild(li);
    });

    // Highlight active heading as user scrolls
    const tocLinks  = tocList.querySelectorAll('.toc-link');
    let   activeLink = null;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const link = tocList.querySelector(`[data-heading-id="${entry.target.id}"]`);
            if (!link) return;
            if (activeLink) {
                activeLink.classList.remove('fw-semibold', 'text-primary');
                activeLink.classList.add('text-body-secondary');
            }
            activeLink = link;
            link.classList.remove('text-body-secondary');
            link.classList.add('fw-semibold', 'text-primary');
        });
    }, { rootMargin: '0px 0px -60% 0px', threshold: 0 });

    headings.forEach(h => observer.observe(h));
});
