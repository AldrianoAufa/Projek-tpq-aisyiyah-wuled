import React from 'react';
import Link from 'next/link';

export default function DashboardPage() {
  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        <header className="flex justify-between items-center mb-8 bg-white p-4 rounded shadow">
          <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
          <nav>
            <Link href="/login" className="text-red-500 hover:underline">
                Logout
            </Link>
          </nav>
        </header>

        <div className="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-8" role="alert">
          <p className="font-bold">Informasi Mode Development</p>
          <p>Dashboard ini adalah tampilan dasar. Untuk menampilkan data asli, Anda perlu menghubungkan aplikasi ini dengan Google Sheets Anda melalui Vercel Environment Variables.</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-white p-6 rounded-lg shadow border border-gray-200">
            <h2 className="text-xl font-semibold text-gray-800 mb-2">Total Siswa</h2>
            <p className="text-4xl font-bold text-blue-600">--</p>
          </div>
          <div className="bg-white p-6 rounded-lg shadow border border-gray-200">
            <h2 className="text-xl font-semibold text-gray-800 mb-2">Total Pembayaran Bulan Ini</h2>
            <p className="text-4xl font-bold text-green-600">Rp --</p>
          </div>
          <div className="bg-white p-6 rounded-lg shadow border border-gray-200">
            <h2 className="text-xl font-semibold text-gray-800 mb-2">Tunggakan SPP</h2>
            <p className="text-4xl font-bold text-red-600">--</p>
          </div>
        </div>
      </div>
    </div>
  );
}
