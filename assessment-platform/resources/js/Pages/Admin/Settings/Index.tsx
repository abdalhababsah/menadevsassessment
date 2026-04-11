import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Props {
    settings: {
        platform_name: string;
        support_email: string;
        default_passing_score: number;
        require_camera_verification: boolean;
        enforce_anti_cheat: boolean;
    };
}

export default function Index({ settings }: Props) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        platform_name: settings.platform_name || 'MENA Devs Assessment',
        support_email: settings.support_email || 'support@menadevs.com',
        default_passing_score: settings.default_passing_score || 70,
        require_camera_verification: Boolean(settings.require_camera_verification),
        enforce_anti_cheat: settings.enforce_anti_cheat ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.settings.update'));
    };

    return (
        <AdminLayout>
            <Head title="Platform Settings" />

            <div className="mx-auto max-w-3xl">
                <div className="mb-6">
                    <h2 className="text-2xl font-bold text-gray-900">Platform Settings</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Manage global platform configurations and proctoring defaults.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div className="px-6 py-5">
                            <h3 className="text-lg font-medium text-gray-900">General Information</h3>
                            <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <label htmlFor="platform_name" className="block text-sm font-medium text-gray-700">Platform Name</label>
                                    <input
                                        id="platform_name"
                                        type="text"
                                        value={data.platform_name}
                                        onChange={(e) => setData('platform_name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 py-2 px-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.platform_name && <p className="mt-2 text-sm text-red-600">{errors.platform_name}</p>}
                                </div>
                                <div className="sm:col-span-2">
                                    <label htmlFor="support_email" className="block text-sm font-medium text-gray-700">Support Email</label>
                                    <input
                                        id="support_email"
                                        type="email"
                                        value={data.support_email}
                                        onChange={(e) => setData('support_email', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 py-2 px-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.support_email && <p className="mt-2 text-sm text-red-600">{errors.support_email}</p>}
                                </div>
                                <div className="sm:col-span-2">
                                    <label htmlFor="default_passing_score" className="block text-sm font-medium text-gray-700">Default Passing Score (%)</label>
                                    <input
                                        id="default_passing_score"
                                        type="number"
                                        step="0.1"
                                        value={data.default_passing_score}
                                        onChange={(e) => setData('default_passing_score', parseFloat(e.target.value))}
                                        className="mt-1 block w-full rounded-md border-gray-300 py-2 px-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.default_passing_score && <p className="mt-2 text-sm text-red-600">{errors.default_passing_score}</p>}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div className="px-6 py-5">
                            <h3 className="text-lg font-medium text-gray-900">Proctoring & Security</h3>
                            <div className="mt-6 space-y-4">
                                <div className="flex items-start">
                                    <div className="flex h-5 items-center">
                                        <input
                                            id="require_camera_verification"
                                            type="checkbox"
                                            checked={data.require_camera_verification}
                                            onChange={(e) => setData('require_camera_verification', e.target.checked)}
                                            className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                    </div>
                                    <div className="ml-3 text-sm">
                                        <label htmlFor="require_camera_verification" className="font-medium text-gray-700">Require Camera Verification by Default</label>
                                        <p className="text-gray-500">If enabled, all new quizzes will have camera verification enabled by default.</p>
                                    </div>
                                </div>
                                <div className="flex items-start">
                                    <div className="flex h-5 items-center">
                                        <input
                                            id="enforce_anti_cheat"
                                            type="checkbox"
                                            checked={data.enforce_anti_cheat}
                                            onChange={(e) => setData('enforce_anti_cheat', e.target.checked)}
                                            className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                    </div>
                                    <div className="ml-3 text-sm">
                                        <label htmlFor="enforce_anti_cheat" className="font-medium text-gray-700">Enforce Anti-Cheat Features</label>
                                        <p className="text-gray-500">Disables pasting and enforces full screen for all quizzes globally.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end items-center gap-4">
                        {recentlySuccessful && <p className="text-sm text-green-600 font-medium">Settings saved!</p>}
                        <button
                            type="submit"
                            disabled={processing}
                            className={`rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ${processing ? 'opacity-50 cursor-not-allowed' : ''}`}
                        >
                            {processing ? 'Saving...' : 'Save Settings'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
