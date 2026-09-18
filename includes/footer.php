<?php if (!str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/')): ?>
<footer class="footer"><div class="brand">ARENA<span>FLOW</span></div><p>Official tournament management & live scoring system.</p><small>&copy; <?= date('Y') ?> ArenaFlow</small></footer>
<?php endif; ?>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
</body></html>
