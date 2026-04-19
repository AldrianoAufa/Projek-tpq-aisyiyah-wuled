'use client';

import React from 'react';
import Link from 'next/link';

export default function LoginPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="bg-white p-8 rounded shadow-md w-96">
        <h1 className="text-2xl font-bold mb-6 text-center text-gray-800">Login</h1>
        <form className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700">Username / Kode Login</label>
            <input
              type="text"
              className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 border p-2"
              placeholder="Masukkan username/kode"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700">Password</label>
            <input
              type="password"
              className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 border p-2"
              placeholder="Masukkan password"
            />
          </div>
          <button
            type="button"
            className="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
            onClick={() => alert("Autentikasi ini adalah mock. Implementasi sebenarnya membutuhkan konfigurasi Google Sheets di .env")}
          >
            Login
          </button>
        </form>
        <div className="mt-4 text-center">
            <Link href="/dashboard" className="text-blue-500 hover:underline text-sm">
                Lihat Contoh Dashboard (Mock)
            </Link>
        </div>
      </div>
    </div>
  );
}
