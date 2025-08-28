<x-mail::message>
Pesanan Dikirim! 🎉
Halo {{ $user->name ?? 'Pelanggan' }},

Pesanan Anda dengan nomor #{{ $bill->id }} telah dikirim. Kami berharap Anda menyukainya!

Rincian Pesanan
Nomor Pesanan: {{ $payment->id }}

Tanggal Kirim: {{ now()->format('d M Y') }}

Metode Pembayaran: {{ $payment->payment_method }}

Terima kasih,<br>
PayTrack
</x-mail::message>