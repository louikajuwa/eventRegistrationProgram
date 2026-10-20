// Reusable AJAX data table: server-side search + sort + filter + pagination.
// Usage:
//   const dt = new DataTable({
//     url: '/events',
//     mount: '#dt',                 // container with [data-dt-body], [data-dt-pagination], [data-dt-empty]
//     columns: [{ key:'name', label:'Name', sortable:true }, ...],
//     row: (item) => `<tr>...</tr>`,   // returns a <tr> string
//     searchInput: '#dt-search',
//     filters: { '#dt-status': 'is_active' },
//   });
//   dt.reload();
(function ($) {
  class DataTable {
    constructor(cfg) {
      this.cfg = cfg;
      this.$root = $(cfg.mount);
      this.$body = this.$root.find('[data-dt-body]');
      this.$pagination = this.$root.find('[data-dt-pagination]');
      this.$empty = this.$root.find('[data-dt-empty]');
      this.state = { page: 1, sort_by: cfg.defaultSort || 'id', sort_dir: cfg.defaultDir || 'desc', search: '', per_page: cfg.perPage || 15, filters: {} };
      this.pendingRows = [];
      this._bind();
    }

    _bind() {
      const self = this;
      // Sort headers (keyboard accessible)
      this.$root.find('[data-dt-sort]').attr({ tabindex: 0, 'aria-sort': 'none' });
      this._syncSortAria();
      this.$root.on('keydown', '[data-dt-sort]', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
      });
      this.$root.on('click', '[data-dt-sort]', function () {
        const key = $(this).data('dt-sort');
        if (self.state.sort_by === key) {
          self.state.sort_dir = self.state.sort_dir === 'asc' ? 'desc' : 'asc';
        } else {
          self.state.sort_by = key;
          self.state.sort_dir = 'asc';
        }
        self.state.page = 1;
        self._syncSortAria();
        self.reload();
        self.$root.find('[data-dt-sort="' + key + '"]').trigger('focus');
      });
      // Retry after a failed load
      this.$root.on('click', '[data-dt-retry]', function () { self.reload(); });
      // Search (debounced)
      if (this.cfg.searchInput) {
        let t;
        $(this.cfg.searchInput).on('input', function () {
          clearTimeout(t);
          const v = this.value;
          t = setTimeout(function () { self.state.search = v; self.state.page = 1; self.reload(); }, 300);
        });
      }
      // Filters
      if (this.cfg.filters) {
        Object.keys(this.cfg.filters).forEach(function (sel) {
          $(sel).on('change', function () {
            self.state.filters[self.cfg.filters[sel]] = this.value;
            self.state.page = 1;
            self.reload();
          });
        });
      }
      // Pagination
      this.$root.on('click', '[data-dt-page]', function (e) {
        e.preventDefault();
        self.state.page = parseInt($(this).data('dt-page'), 10);
        self.reload();
      });
    }

    _syncSortAria() {
      const s = this.state;
      this.$root.find('[data-dt-sort]').each(function () {
        const active = $(this).data('dt-sort') === s.sort_by;
        $(this).attr('aria-sort', active ? (s.sort_dir === 'asc' ? 'ascending' : 'descending') : 'none');
      });
    }

    params() {
      return Object.assign({
        page: this.state.page,
        sort_by: this.state.sort_by,
        sort_dir: this.state.sort_dir,
        search: this.state.search,
        per_page: this.state.per_page,
      }, this.state.filters);
    }

    reload() {
      const self = this;
      this.$body.html('<tr><td colspan="99" class="p-6 text-center" style="color:var(--text-muted)"><span class="spinner" style="border-color:#cbd5e1;border-top-color:#475569"></span> Loading…</td></tr>');
      return $.ajax({ url: this.cfg.url, data: this.params(), dataType: 'json', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .done(function (res) { self._render(res); })
        .fail(function () { self.$body.html('<tr><td colspan="99" class="p-6 text-center" style="color:var(--c-danger)">Failed to load. <button type="button" class="btn btn-ghost" data-dt-retry>Retry</button></td></tr>'); window.toast && window.toast('Failed to load data', 'error'); });
    }

    _render(res) {
      const items = res.data || [];
      this.items = items;
      this.pendingRows = [];
      if (!items.length) {
        this.$body.html('');
        this.$empty.removeClass('hidden');
        this.$pagination.html('');
        return;
      }
      this.$empty.addClass('hidden');
      this.$body.html(items.map(this.cfg.row).join(''));
      this._paginate(res.meta || {});
    }

    _paginate(meta) {
      if (!meta.last_page || meta.last_page <= 1) { this.$pagination.html(''); return; }
      const cur = meta.current_page, last = meta.last_page;
      let html = '<nav aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-2 text-sm" style="color:var(--text-muted)">';
      html += `<span>Showing ${meta.from || 0}–${meta.to || 0} of ${meta.total}</span><div class="flex flex-wrap gap-1">`;
      const btn = (p, label, disabled, active, aria) =>
        `<button type="button" data-dt-page="${p}" class="btn ${active ? 'btn-primary' : 'btn-secondary'}" style="height:32px;padding:0 .6rem" ${disabled ? 'disabled' : ''}` +
        `${aria ? ` aria-label="${aria}"` : ` aria-label="Page ${p}"`}${active ? ' aria-current="page"' : ''}>${label}</button>`;
      html += btn(cur - 1, 'Prev', cur <= 1, false, 'Previous page');
      const start = Math.max(1, cur - 2), end = Math.min(last, cur + 2);
      for (let p = start; p <= end; p++) html += btn(p, p, false, p === cur);
      html += btn(cur + 1, 'Next', cur >= last, false, 'Next page');
      html += '</div></nav>';
      this.$pagination.html(html);
    }

    getItem(id) {
      return (this.items || []).find(function (i) { return String(i.id) === String(id); });
    }

    // Real-time hook: called by realtime.js when a row is pushed.
    onPushedRow(item) {
      if (this.state.page === 1 && !this.state.search && Object.values(this.state.filters).every(v => !v)) {
        this.reload(); // safe to refresh page 1 with no active filter
      } else {
        this.pendingRows.push(item);
        this.$root.find('[data-dt-newbanner]').removeClass('hidden').find('[data-dt-newcount]').text(this.pendingRows.length);
      }
    }
  }

  window.DataTable = DataTable;
})(jQuery);
