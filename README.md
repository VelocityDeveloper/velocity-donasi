# Plugin Velocity Donasi

Plugin donasi untuk projek Velocity Developer

Version: 3.1.0

web demo: https://donasi.velocitydeveloper.com/

## Pembayaran DOKU (VD Payment Gateway)

Bila plugin **VD Payment Gateway** aktif dan gateway DOKU dinyalakan, form donasi menampilkan pilihan
"Payment Gateway (DOKU)". Alurnya:

- Submit donasi → tagihan DOKU dibuat (`source` = `velocity-donasi`, `ref_id` = ID donasi-masuk), status donasi
  **Menunggu Pembayaran**, donatur mendapat tombol bayar dan email berisi link ke halaman donatur (`?inv=`).
- Notifikasi DOKU: lunas → **Sukses**, kedaluwarsa → **Gagal**, refund → **Refund**. Action
  `velocity_donasi_payment_{paid|expired|refunded}` (`$post_id, $transaction, $payload`).
- Link bayar di riwayat donasi dibuat ulang otomatis bila kedaluwarsa (maks. 1x per 5 menit).
- Invoice dari form diganti invoice baru bila sudah dipakai donasi/transaksi lain. Nominal minimal Rp10.000 dicek di server.

Transfer bank dan Duitku tidak berubah.
