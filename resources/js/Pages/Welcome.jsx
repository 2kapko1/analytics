import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 text-black dark:bg-black dark:text-white">
                <div className="text-center">
                    <h1 className="mb-4 text-4xl font-bold">Hello</h1>
                    <p className="mb-8 text-lg text-gray-600 dark:text-gray-400">
                        Welcome to our analytics platform.
                    </p>
                    <div className="flex flex-col items-center gap-4">
                        <a
                            href="https://visitify.pl"
                            className="rounded-md bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Visit visitify.pl
                        </a>

                        <div className="mt-8 flex gap-4">
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="text-sm font-medium text-gray-600 underline transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="text-sm font-medium text-gray-600 underline transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                                    >
                                        Log in
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
