# TPQ Next.js dengan Google Sheets

Project ini adalah awal mula migrasi dari aplikasi PHP menjadi aplikasi Next.js (App Router) yang menggunakan **Google Sheets** sebagai databasenya, dan siap untuk di-deploy ke **Vercel**.

## Cara Setup Google Sheets API

Agar aplikasi ini dapat membaca dan menulis data ke Google Sheets, Anda perlu melakukan konfigurasi berikut:

1. **Buat Project di Google Cloud Console**:
   - Buka [Google Cloud Console](https://console.cloud.google.com/).
   - Buat project baru.

2. **Aktifkan Google Sheets API**:
   - Di menu sebelah kiri, masuk ke **APIs & Services > Library**.
   - Cari "Google Sheets API" dan klik **Enable**.

3. **Buat Service Account**:
   - Masuk ke **APIs & Services > Credentials**.
   - Klik **Create Credentials > Service account**.
   - Isi nama service account (misal: `tpq-sheets-db`) dan klik **Create and Continue**, lalu **Done**.

4. **Buat Kunci (Key) Service Account**:
   - Klik pada email service account yang baru saja dibuat.
   - Masuk ke tab **Keys**, klik **Add Key > Create new key**.
   - Pilih **JSON** dan klik **Create**. File JSON akan terdownload.

5. **Buka File JSON**:
   - Buka file JSON yang terdownload. Anda akan membutuhkan `client_email` dan `private_key` dari file ini.

6. **Bagikan Spreadsheet Anda**:
   - Buat file Google Sheets baru.
   - Klik tombol **Share** di pojok kanan atas.
   - Masukkan `client_email` (dari file JSON tadi) ke kolom "Add people and groups".
   - Set permission menjadi **Editor**.
   - Salin **ID Spreadsheet** Anda. ID ini berada di URL.
     (Contoh: `https://docs.google.com/spreadsheets/d/INI_ADALAH_ID_SPREADSHEET_ANDA/edit`)

## Konfigurasi Environment Variables (Vercel)

Saat Anda mendeploy project ini ke Vercel, Anda wajib menambahkan Environment Variables berikut di menu Settings > Environment Variables:

- `GOOGLE_SERVICE_ACCOUNT_EMAIL`: Isi dengan `client_email` dari JSON tadi.
- `GOOGLE_PRIVATE_KEY`: Isi dengan `private_key` dari JSON. **Pastikan untuk meng-copy seluruh key termasuk `-----BEGIN PRIVATE KEY-----` dan `\n`.**
- `GOOGLE_SHEET_ID`: Isi dengan ID Spreadsheet yang Anda salin dari URL.

## Pengembangan Lokal

Jika Anda ingin menjalankan aplikasi ini di komputer Anda sendiri:

1. Buat file `.env.local` di folder `nextjs-app`.
2. Isi dengan format berikut:
   ```env
   GOOGLE_SERVICE_ACCOUNT_EMAIL="email-service-account-anda@project-anda.iam.gserviceaccount.com"
   GOOGLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nContohKeyAnda...\n-----END PRIVATE KEY-----\n"
   GOOGLE_SHEET_ID="id-spreadsheet-anda"
   ```
3. Jalankan `npm run dev`.

## Catatan Penting
Migrasi ini dilakukan secara bertahap. Versi saat ini hanya memuat _skeleton_ (kerangka dasar) berupa Login dan Dashboard, beserta library koneksi ke Google Sheets. Logika kompleks seperti CRUD data siswa, kelas, dan pembayaran belum diimplementasikan karena bergantung pada struktur tabel/sheet yang Anda buat di Google Sheets.