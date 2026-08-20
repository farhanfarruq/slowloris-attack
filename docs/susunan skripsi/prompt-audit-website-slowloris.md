# Prompt Audit Website Slowloris Lab

Gunakan prompt ini untuk meminta AI/reviewer menganalisis website Slowloris Lab secara ketat. Tujuannya bukan mencari pembenaran, tetapi menemukan penyimpangan antara tampilan, data, validasi, logika scoring, dan bukti nyata dari Wireshark + Snort.

## Prompt Utama

```text
Kamu bertindak sebagai auditor teknis senior untuk aplikasi riset keamanan siber bernama Slowloris Lab.

Konteks aplikasi:
- Aplikasi ini adalah dashboard lab lokal untuk eksperimen Slowloris/Slow HTTP pada VM sendiri.
- Akuisisi wajib berasal dari Wireshark/dumpcap dalam format .pcapng.
- Validasi wajib berasal dari Snort dalam format .log.
- Target lab adalah VM lokal, bukan target publik.
- Jangan menerima data simulasi, seed palsu, atau hasil AI yang dibuat-buat.
- Jangan mengarang hasil. Semua kesimpulan harus bersumber dari file, kode, database, atau output command yang benar-benar ada.

Tugas audit:
1. Audit tampilan website.
   - Periksa apakah dashboard, halaman upload akuisisi, upload validasi, analisis, AI check, grafik, dataset, evaluasi, laporan, dan audit log menampilkan istilah yang benar.
   - Cari label yang menyesatkan, misalnya AI confidence yang disalahartikan sebagai confidence serangan.
   - Pastikan UI membedakan "Traffic Normal", "Suspicious", "Attack Detected", dan "Inconclusive" secara akurat.
   - Pastikan baseline normal seperti HTTP Burst dan iPerf3 tidak ditampilkan sebagai Slowloris attack kecuali ada bukti kuat.

2. Audit alur data.
   - Telusuri alur dari upload file akuisisi .pcapng sampai tersimpan ke database.
   - Telusuri alur dari upload file validasi Snort .log sampai dipasangkan ke akuisisi yang benar.
   - Pastikan validasi tidak bisa berjalan tanpa pasangan file akuisisi + validasi.
   - Pastikan file dari eksperimen berbeda tidak tertukar.
   - Pastikan eksperimen manual tidak tertumpuk tanpa kontrol atau duplikasi yang jelas.

3. Audit logika Slowloris.
   - Periksa apakah indikator Slowloris benar:
     - koneksi HTTP banyak dan bertahan lama,
     - header tidak selesai atau slow headers,
     - throughput rendah dibanding jumlah koneksi,
     - alert Snort relevan dengan Slow HTTP/Slowloris,
     - korelasi waktu antara capture Wireshark dan alert Snort.
   - Pastikan HTTP Burst biasa tidak otomatis dianggap Slowloris hanya karena request tinggi.
   - Pastikan iPerf3 bandwidth baseline tidak dianggap serangan Slowloris.
   - Pastikan port scan tidak dicampur dengan klasifikasi Slowloris.

4. Audit scoring dan status.
   - Baca kode scoring, bukan hanya tampilan.
   - Jelaskan rumus final score dan ambang status.
   - Pastikan final score, attack category, experiment_status, dan final decision konsisten.
   - Cari kondisi ketika score 55, >55, <=30, dan lainnya bisa menghasilkan status yang salah.
   - Pastikan status attack_detected hanya muncul untuk bukti Slowloris yang kuat, bukan karena confidence AI yang salah konteks.

5. Audit AI validation.
   - Pastikan aplikasi tidak menghasilkan hasil AI palsu saat API key kosong.
   - Pastikan hasil AI live disimpan dengan raw response dan provider yang benar.
   - Pastikan confidence dari klasifikasi "Suspicious" tidak dihitung sebagai confidence "Slowloris Detected".
   - Pastikan hasil AI hanya membantu validasi, bukan mengganti bukti Wireshark + Snort.
   - Cari risiko hallucination dari prompt AI dan rekomendasikan guardrail.

6. Audit file lab dan script shell.
   - Pastikan script VM hanya menargetkan subnet lab sendiri, misalnya 192.168.56.0/24.
   - Pastikan script tidak menyerang target publik.
   - Pastikan capture memakai Wireshark/dumpcap dan validasi memakai Snort.
   - Cari script lama yang memakai flow tidak sesuai, data lama, atau nama file yang membingungkan.
   - Pastikan output script jelas:
     - file akuisisi: *-wireshark.pcapng
     - file validasi: *-snort.log

7. Audit database dan seeder.
   - Pastikan tidak ada DemoDataSeeder atau data contoh yang terlihat sebagai data riset asli.
   - Pastikan reset data riset tidak menghapus user/API setting jika memang dirancang begitu.
   - Pastikan import capture lokal hanya mengimpor pasangan file yang valid.

8. Audit eksperimen yang ada.
   - Periksa setiap eksperimen:
     - slow-http,
     - http-burst,
     - iperf-bandwidth,
     - portscan.
   - Untuk masing-masing, cocokkan:
     - nama eksperimen,
     - traffic_type,
     - ground_truth_label,
     - file akuisisi,
     - file validasi,
     - jumlah packet,
     - jumlah koneksi,
     - jumlah alert Snort,
     - final score,
     - attack category,
     - experiment_status,
     - hasil AI.
   - Tandai jika ada mismatch, misalnya baseline normal masuk Attack Detected.

9. Audit keamanan aplikasi.
   - Periksa upload file:
     - validasi ekstensi,
     - validasi MIME,
     - ukuran file,
     - storage path,
     - risiko file traversal,
     - risiko upload berbahaya.
   - Periksa penyimpanan API key:
     - apakah terenkripsi,
     - apakah tidak tampil ulang plaintext,
     - apakah bisa dihapus.
   - Periksa authorization:
     - apakah user hanya bisa mengakses datanya sendiri,
     - apakah route sensitif dilindungi login.

10. Audit output dan laporan.
   - Pastikan laporan tidak menyebut "serangan asli" jika hanya suspicious.
   - Pastikan istilah "serangan asli" hanya dipakai untuk Attack Detected dengan bukti valid.
   - Pastikan laporan menampilkan keterbatasan eksperimen lab.
   - Pastikan laporan menyebut bahwa target adalah VM lokal milik sendiri.

Aturan jawaban:
- Jangan mengarang.
- Jika belum membaca file/kode/database, tulis "belum diverifikasi".
- Setiap temuan harus menyebut file dan baris kode jika memungkinkan.
- Bedakan "bug", "risiko", "mismatch data", "UI misleading", dan "perlu keputusan desain".
- Untuk setiap temuan, berikan:
  - severity: Critical / High / Medium / Low,
  - bukti,
  - dampak,
  - rekomendasi fix,
  - cara verifikasi setelah fix.
- Jika sesuatu sudah benar, jelaskan bukti singkatnya.
```

## Prompt Audit Cepat Dashboard

```text
Audit khusus dashboard Slowloris Lab.

Periksa apakah angka dan label di dashboard konsisten dengan database dan logic backend:
- total file akuisisi,
- total file validasi,
- total alert Snort,
- koneksi mencurigakan,
- confidence jawaban AI,
- total eksperimen,
- status Traffic Normal/Suspicious/Attack Detected/Inconclusive/Belum Dianalisis,
- daftar eksperimen terbaru.

Cari mismatch seperti:
- baseline HTTP Burst tampil Attack Detected,
- iPerf3 tampil serangan Slowloris,
- AI confidence disalahartikan sebagai confidence serangan,
- final score tidak cocok dengan attack category,
- experiment_status tidak cocok dengan attack_category.

Berikan tabel temuan dengan kolom: Komponen UI, Data Sumber, Kondisi Aktual, Kondisi Seharusnya, Severity, Fix.
Jangan menyimpulkan tanpa membaca kode dan data.
```

## Prompt Audit Scoring Slowloris

```text
Audit khusus scoring Slowloris.

Baca service scoring dan service analisis. Jelaskan:
- fitur apa saja yang dihitung,
- sumber fitur dari Wireshark/dumpcap,
- sumber fitur dari Snort,
- bobot final score,
- threshold kategori,
- mapping kategori ke experiment_status,
- mapping kategori ke final decision.

Uji secara konseptual 4 skenario:
1. slow-http harus cenderung Attack Detected jika koneksi panjang dan alert relevan.
2. http-burst harus maksimal Suspicious jika hanya request tinggi tanpa pola slow headers.
3. iperf-bandwidth harus Normal/Suspicious ringan, bukan Slowloris.
4. portscan tidak boleh diklasifikasikan Slowloris.

Cari false positive dan false negative. Beri rekomendasi perubahan rumus tanpa memalsukan hasil.
```

## Prompt Audit AI

```text
Audit khusus AI validation.

Pastikan sistem tidak memakai hasil AI simulasi. Pastikan AI hanya berjalan jika provider live aktif dan API key valid.

Periksa:
- konfigurasi provider,
- penyimpanan API key,
- prompt yang dikirim ke model,
- payload yang dikirim,
- parsing respons model,
- penyimpanan raw request/raw response,
- penggunaan confidence score dalam final score.

Aturan yang harus dipenuhi:
- confidence dari "Slowloris Detected" boleh menambah skor Slowloris.
- confidence dari "Suspicious" tidak boleh dihitung sebagai confidence serangan Slowloris.
- confidence dari "Normal" dan "Inconclusive" tidak boleh menaikkan skor serangan.
- AI tidak boleh mengalahkan bukti Wireshark + Snort.

Laporkan semua penyimpangan beserta file dan baris kode.
```

## Prompt Audit Lab VM

```text
Audit khusus script VM lab.

Periksa semua script di scripts/vm-lab.
Pastikan:
- hanya berjalan di subnet lab lokal 192.168.56.0/24,
- target default adalah VM target lokal,
- attacker default adalah VM attacker lokal,
- tidak ada target publik,
- capture memakai dumpcap/Wireshark,
- validasi memakai Snort,
- output file konsisten,
- sudo terbatas tidak terlalu luas,
- proses background tidak membuat runner macet,
- file hasil bisa dicopy ke storage/app/vm-lab-captures.

Cari script lama yang tidak sesuai flow Wireshark + Snort. Jika ada, rekomendasikan hapus atau rename.
```

## Prompt Audit Evidence Eksperimen

```text
Audit bukti eksperimen.

Untuk setiap eksperimen, cocokkan file berikut:
- {scenario}-wireshark.pcapng
- {scenario}-snort.log

Validasi:
- file ada,
- ukuran masuk akal,
- timestamp eksperimen cocok,
- source_ip dan target_ip cocok,
- port/protokol cocok dengan skenario,
- alert Snort relevan dengan skenario,
- jumlah koneksi dan packet tidak kosong,
- hasil parser masuk akal.

Output harus berupa tabel:
Eksperimen | Akuisisi | Validasi | Cocok/Tidak | Bukti | Status Seharusnya | Catatan

Jangan memakai data seed. Jangan mengisi angka yang tidak berasal dari file atau database.
```

