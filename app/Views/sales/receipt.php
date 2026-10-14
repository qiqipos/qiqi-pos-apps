<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title>Struk <?= esc($sale['invoice_number']) ?></title>
<style>
 body{font-family:monospace;font-size:12px;width:280px;margin:8px auto;color:#000}
 .c{text-align:center}.r{text-align:right}hr{border:0;border-top:1px dashed #000}table{width:100%;border-collapse:collapse}td{vertical-align:top}
 @media print{button{display:none}}
</style></head>
<body onload="window.print()">
<div class="c"><b><?= esc($store['name'] ?? 'Qiqi POS') ?></b><br><?= esc($store['address'] ?? '') ?><br><?= esc($store['phone'] ?? '') ?></div><hr>
<?= esc($sale['invoice_number']) ?><br><?= tanggal_indo($sale['sale_date'], true) ?><br>Kasir: <?= esc($sale['cashier_name']) ?><br>Pelanggan: <?= esc($sale['customer_name']) ?><hr>
<table><?php foreach ($items as $i): ?>
<tr><td colspan="2"><?= esc($i['name']) ?></td></tr>
<tr><td><?= rtrim(rtrim(number_format((float) $i['quantity'], 3, ',', '.'), '0'), ',') ?> x <?= rupiah($i['unit_price'], false) ?></td><td class="r"><?= rupiah((float) $i['unit_price'] * (float) $i['quantity'], false) ?></td></tr>
<?php if ((float) $i['discount_amount'] > 0): ?><tr><td>&nbsp;&nbsp;Diskon</td><td class="r">-<?= rupiah($i['discount_amount'], false) ?></td></tr><?php endif; ?>
<?php endforeach; ?></table><hr>
<table>
<tr><td>Subtotal</td><td class="r"><?= rupiah($sale['subtotal'], false) ?></td></tr>
<tr><td>Diskon</td><td class="r">-<?= rupiah($sale['discount_amount'], false) ?></td></tr>
<tr><td>Pajak</td><td class="r"><?= rupiah($sale['tax_amount'], false) ?></td></tr>
<tr><td><b>TOTAL</b></td><td class="r"><b><?= rupiah($sale['total_amount'], false) ?></b></td></tr>
<tr><td>Bayar (<?= esc(metode_bayar_label($sale['payment_method'])) ?>)</td><td class="r"><?= rupiah($sale['paid_amount'], false) ?></td></tr>
<tr><td>Kembali</td><td class="r"><?= rupiah($sale['change_amount'], false) ?></td></tr></table>
<?php if ($sale['status'] === 'VOID'): ?><hr><div class="c"><b>*** DIBATALKAN ***</b></div><?php endif; ?>
<hr><div class="c">Terima kasih atas kunjungan Anda</div><br><button onclick="window.print()">Cetak</button>
</body></html>
