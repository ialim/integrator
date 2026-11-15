import { RouterProvider, type RouterHistory } from '@tanstack/react-router';
import { useState } from 'react';
import { createAppRouter } from './router';
import { ShellContext, type ShellServices } from './shell';

export function App({ services, history }: { services: ShellServices; history?: RouterHistory }) {
    const [router] = useState(() => createAppRouter(history));

    return (
        <ShellContext.Provider value={services}>
            <RouterProvider router={router} />
        </ShellContext.Provider>
    );
}
