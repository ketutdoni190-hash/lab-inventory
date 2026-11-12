        </div>
        <footer class="footer">&copy; <?= date('Y') ?> <?= e(APP_FULLNAME) ?></footer>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Konfirmasi untuk form yang memiliki atribut data-confirm
document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', ev => {
    if (!confirm(f.dataset.confirm)) ev.preventDefault();
}));
</script>
</body>
</html>
