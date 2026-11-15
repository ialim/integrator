import { useEffect, type ReactNode } from 'react';

/**
 * The one template for every text page (UX-DR30): the page's single top
 * heading, then its words in one readable column.
 */
export function TextPage({ title, children }: { title: string; children: ReactNode }) {
    useEffect(() => {
        document.title = `${title} · integrator`;
    }, [title]);

    return (
        <article className="text-page" aria-labelledby="page-title">
            <h1 id="page-title" className="page-title">
                {title}
            </h1>
            {children}
        </article>
    );
}
