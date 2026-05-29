<!-- === Akhir Konten Halaman === -->
        </div><!-- /overflow-y-auto -->
    </div><!-- /flex-1 -->
</div><!-- /flex h-screen -->

<?php if (!empty($extraScript)) echo $extraScript; ?>

<!-- Script JavaScript Dasar untuk Jam Realtime -->
<script>
    // Jalankan fungsi ini setiap 1 detik (1000 milidetik)
    setInterval(function() {
        // Ambil waktu saat ini
        var waktuSekarang = new Date();
        
        // Ambil jam, menit, detik dan pastikan selalu 2 digit (contoh: 09 bukan 9)
        var jam = String(waktuSekarang.getHours()).padStart(2, '0');
        var menit = String(waktuSekarang.getMinutes()).padStart(2, '0');
        var detik = String(waktuSekarang.getSeconds()).padStart(2, '0');
        
        // Gabungkan menjadi format HH:MM:SS
        var formatWaktu = jam + ':' + menit + ':' + detik;
        
        // Cari elemen HTML dengan ID 'realtime-clock' dan ubah teksnya
        var elemenJam = document.getElementById('realtime-clock');
        if (elemenJam) {
            elemenJam.innerText = formatWaktu;
        }
    }, 1000);
</script>

</body>
</html>