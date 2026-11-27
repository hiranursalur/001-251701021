<?php
declare(strict_types=1);

function adim(array $s, string $a): array {
    if ($s['bitti']) return $s;

    if ($a === 'M') {
        if ($s['gunluk_musteri'] >= 5) return $s;
        $s['musteri_sira']++;
        $s['gunluk_musteri']++;
        $m = musteri($s['no'], $s['musteri_sira']);
        $u = $m['urun'];
        $ad = $s['urunler'][$u]['ad'];
        $fiyat = $s['urunler'][$u]['fiyat'];
        $bas = $m['ad'] . ' (' . $m['tip'] . ', bütçe ' . $m['butce'] . ' TL) ' . $ad . ' istiyor → ';

        $kesinti = $s['elektrik'] > 0;
        if ($kesinti) $s['elektrik']--;

        if (!$kesinti && $s['urunler'][$u]['stok'] > 0 && $m['butce'] >= $fiyat) {
            $odenen = $fiyat;
            if ($m['tip'] === 'Öğrenci') $odenen = intdiv($fiyat * 80, 100);
            $s['kasa'] += $odenen;
            $s['urunler'][$u]['stok']--;
            $s['memnuniyet'] = min(100, $s['memnuniyet'] + 2);
            $s['seri']++;
            $msg = 'Satış: 1 adet (+' . $odenen . ' TL)';
            if ($s['seri'] === 3) {
                $s['kasa'] += 10;
                $s['seri'] = 0;
                $msg .= ' + seri bonusu 10 TL';
            }
            $s['log'][] = $bas . $msg;
        } else {
            $s['seri'] = 0;
            $s['memnuniyet'] = max(0, $s['memnuniyet'] - 8);
            $s['ret_sayac'][$u]++;
            $s['gunluk_ret']++;
            $s['log'][] = $bas . 'Satış yok';
        }

        $o = olay($s['no'], $s['musteri_sira']);
        if ($o === 'elektrik') {
            $s['elektrik'] = 1;
            $s['log'][] = 'Elektrik kesintisi';
        } elseif ($o !== null) {
            $s['log'][] = 'Olay: ' . $o;
        }
    }
    elseif ($a === 'G') {
        if ($s['gunluk_musteri'] === 0) return $s;
        $s['kasa'] -= 60;
        foreach (['tost', 'hamburger'] as $k) {
            $s['urunler'][$k]['stok'] -= intdiv($s['urunler'][$k]['stok'], 2);
        }
        $s['log'][] = 'Gün ' . $s['gun'] . ' kapandı';
        $s['gun']++;
        $s['gunluk_musteri'] = 0;
        $s['gunluk_ret'] = 0;
    }
    elseif (strpos($a, 'T-') === 0) {
        $p = explode('-', $a);
        $k = $p[1];
        $adet = (int)$p[2];
        $s['kasa'] -= $s['urunler'][$k]['fiyat'] * 2 * $adet;
        $s['urunler'][$k]['stok'] += $adet;
        $s['log'][] = $s['urunler'][$k]['ad'] . ' tedarik: ' . $adet . ' adet';
    }
    return $s;
}