import { NextResponse } from 'next/server';
// import { getGoogleSheet } from '@/lib/googleSheets';

export async function POST(request: Request) {
  try {
    const { username, password } = await request.json();

    // IMPLEMENTASI ASLI NANTINYA AKAN SEPERTI INI:
    // 1. Baca spreadsheet
    // const doc = await getGoogleSheet(process.env.GOOGLE_SHEET_ID!);
    // 2. Ambil worksheet khusus 'Users'
    // const sheet = doc.sheetsByTitle['Users'];
    // 3. Cari baris yang sesuai
    // const rows = await sheet.getRows();
    // const user = rows.find(row => row.get('username') === username && row.get('password') === password);

    // MOCK RESPONSE
    if (username === 'admin' && password === 'admin') {
      return NextResponse.json({ success: true, user: { id: 1, role: 'admin' } });
    } else {
      return NextResponse.json({ success: false, message: 'Invalid credentials' }, { status: 401 });
    }
  } catch (error) {
    return NextResponse.json({ success: false, message: 'Server error' }, { status: 500 });
  }
}
