@component('mail::message')
# Pembayaran Berhasil! 🎉

Halo **{{ $bill->user->name ?? 'Pelanggan' }}**,

Pembayaran Anda untuk tagihan **#{{ $bill->invoice_number }}** telah berhasil kami terima. Terima kasih telah menggunakan jasa kami di {{ config('app.name') }}!

---

### Ringkasan Tagihan

* **Nomor Tagihan:** {{ $bill->invoice_number }}
* **Tanggal Pembayaran:** {{ $bill->paid_at->format('d M Y, H:i') }} WIB
* **Total Pembayaran:** Rp{{ number_format($bill->amount, 0, ',', '.') }}

---

@component('mail::button', ['url' => url('/tagihan/' . $bill->id)])
Lihat Detail Tagihan
@endcomponent

---

Jika Anda memiliki pertanyaan lebih lanjut, jangan ragu untuk menghubungi tim dukungan kami.

Hormat kami,
**{{ config('app.name') }}**
@endcomponent