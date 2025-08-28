@component('mail::message')
# Pembayaran Gagal 😥

Halo **{{ $bill->user->name ?? 'Pelanggan' }}**,

Kami ingin menginformasikan bahwa pembayaran Anda untuk tagihan **#{{ $bill->invoice_number }}** tidak berhasil diproses. Ini bisa disebabkan oleh beberapa hal, seperti:
* **Dana tidak mencukupi**
* **Kesalahan teknis** pada bank penerbit
* **Batas waktu pembayaran** yang telah habis

---

### Detail Tagihan

* **Nomor Tagihan:** {{ $bill->invoice_number }}
* **Tanggal Transaksi:** {{ $bill->created_at->format('d M Y, H:i') }} WIB
* **Total Tagihan:** Rp{{ number_format($bill->amount, 0, ',', '.') }}

Jika Anda merasa ini adalah kesalahan atau memiliki pertanyaan, mohon hubungi tim dukungan kami dengan menyertakan nomor tagihan Anda.

Hormat kami,
**{{ config('app.name') }}**
@endcomponent