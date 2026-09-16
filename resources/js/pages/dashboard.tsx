import { Head } from '@inertiajs/react';

const modules = [
  'Market Scoping',
  'PPMP',
  'APP',
  'Procurement Projects',
  'Suppliers',
  'BAC & Evaluation',
  'Contracts',
  'Delivery & Inspection',
];

export default function Dashboard() {
  return (
    <>
      <Head title="Dashboard" />
      <main className="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <div className="max-w-3xl">
          <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">RA 12009 aligned</p>
          <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Procurement Management System</h1>
          <p className="mt-4 text-base leading-7 text-slate-600">
            Operational procurement lifecycle from planning through contract completion, with configurable controls and audit evidence.
          </p>
        </div>

        <section className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {modules.map((module) => (
            <div key={module} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
              <p className="font-medium text-slate-900">{module}</p>
              <p className="mt-2 text-sm text-slate-500">Foundation module</p>
            </div>
          ))}
        </section>
      </main>
    </>
  );
}
