<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransactionRecorded extends Notification
{
    use Queueable;

    // Simpan transaksi yang akan diringkas menjadi notifikasi pengguna.
    public function __construct(private readonly Transaction $transaction)
    {
    }

    // Notifikasi transaksi disimpan di database agar dapat dibuka kembali.
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Bentuk data ringkas yang dipakai menu notifikasi di antarmuka.
    public function toDatabase(object $notifiable): array
    {
        $isIncoming = in_array($this->transaction->type, ['in', 'Barang Masuk'], true);

        return [
            'transaction_id' => $this->transaction->id,
            'product_name' => $this->transaction->product?->name ?? 'Produk',
            'type' => $isIncoming ? 'in' : 'out',
            'quantity' => (int) $this->transaction->quantity,
            'title' => $isIncoming ? 'Barang masuk dikonfirmasi' : 'Barang keluar dikonfirmasi',
            'message' => sprintf(
                '%s unit %s %s.',
                number_format($this->transaction->quantity, 0, ',', '.'),
                $this->transaction->product?->name ?? 'produk',
                $isIncoming ? 'ditambahkan ke stok' : 'dikeluarkan dari stok'
            ),
        ];
    }
}
