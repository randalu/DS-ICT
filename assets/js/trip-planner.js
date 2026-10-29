(function () {
  var form = document.querySelector('[data-planner-form]');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var params = new URLSearchParams(new FormData(form));
    // Drop empties for shareable URLs.
    Array.from(params.keys()).forEach(function (k) {
      if (!params.get(k)) params.delete(k);
    });

    var url = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
    window.location.href = url;
  });
})();

