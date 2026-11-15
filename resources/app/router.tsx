import { createRootRoute, createRoute, createRouter, type RouterHistory } from '@tanstack/react-router';
import { AccessibilityPage, HoldingScreen, InstallPage, NotFoundPage, SignedOutPage } from './pages/pages';
import { Layout } from './shell/Layout';

/*
 * The shell's client routes. Laravel's catch-all serves the same view for
 * every path, so an unknown path arrives with HTTP 200 and is answered here
 * by the not found page. Signed-in screens join from Epic 3.
 */
const root = createRootRoute({ component: Layout, notFoundComponent: NotFoundPage });

const routeTree = root.addChildren([
    createRoute({ getParentRoute: () => root, path: '/', component: HoldingScreen }),
    createRoute({ getParentRoute: () => root, path: '/install', component: InstallPage }),
    createRoute({ getParentRoute: () => root, path: '/accessibility', component: AccessibilityPage }),
    createRoute({ getParentRoute: () => root, path: '/signed-out', component: SignedOutPage }),
]);

export function createAppRouter(history?: RouterHistory) {
    return createRouter({ routeTree, history, scrollRestoration: true });
}

declare module '@tanstack/react-router' {
    interface Register {
        router: ReturnType<typeof createAppRouter>;
    }
}
