<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $document->title }} — Lokon Dokumen Digital</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<style>body{font-family:ui-sans-serif,system-ui,sans-serif}</style>
</head>
<body class="bg-[#dde2e8] min-h-screen flex flex-col">
<div class="flex items-center gap-2 px-3 py-2 bg-[#002f80] text-white text-sm sticky top-0 z-10">
  <img src="{{ asset('images/logo-ypl.png') }}" alt="YPL" class="h-6 w-auto rounded bg-white object-contain">
  <span class="font-bold truncate flex-1">{{ $document->title }}</span>
  <button id="prev" class="w-7 h-7 rounded-full bg-white/15 grid place-items-center hover:bg-white/30">←</button>
  <span class="text-xs font-bold tabular-nums"><span id="now">1</span>/<span id="total">…</span></span>
  <button id="next" class="w-7 h-7 rounded-full bg-white/15 grid place-items-center hover:bg-white/30">→</button>
  <a href="{{ route('documents.show', $document) }}" target="_blank" class="text-xs font-bold bg-white text-[#002f80] rounded-full px-3 py-1">Buka penuh</a>
</div>
<div id="pages" class="flex flex-col items-center gap-4 p-4"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
let pdf = null, page = 1;
async function render() {
  pdf = pdf || await pdfjsLib.getDocument(@json($document->fileUrl())).promise;
  document.getElementById('total').textContent = pdf.numPages;
  page = Math.min(Math.max(1, page), pdf.numPages);
  document.getElementById('now').textContent = page;
  const p = await pdf.getPage(page);
  const vp = p.getViewport({ scale: 1.1 });
  const box = document.getElementById('pages'); box.innerHTML = '';
  const cv = document.createElement('canvas');
  cv.className = 'rounded-lg shadow-xl max-w-full h-auto bg-white';
  cv.height = vp.height; cv.width = vp.width;
  box.appendChild(cv);
  await p.render({ canvasContext: cv.getContext('2d'), viewport: vp }).promise;
}
document.getElementById('prev').onclick = () => { page--; render(); };
document.getElementById('next').onclick = () => { page++; render(); };
render();
</script>
</body>
</html>
