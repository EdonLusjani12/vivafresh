<?php
require __DIR__ . '/inc/layout.php';

$marketsJson = json_encode(
    array_values(markets()),
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);

ob_start();
?>
  <script>
    const markets = <?= $marketsJson ?>;
    const typeNames = { super: "VivaFresh Hyper", regular: "Маркет" };

    function esc(value) {
      const div = document.createElement("div");
      div.textContent = value == null ? "" : String(value);
      return div.innerHTML;
    }

    function generateMarketCard(market) {
      const number = parseInt(market.number, 10);
      const type = market.type === "super" ? "super" : "regular";
      return `
        <div class="market-card">
          <div class="market-header">
            <div class="market-number">${number}</div>
            <span class="market-type ${type}">${typeNames[type]}</span>
          </div>
          <h3 class="market-name">${esc(market.name)}</h3>
          <div class="market-location"><i class="fas fa-map-marker-alt"></i> ${esc(market.city)}</div>
          <button class="view-table-btn" onclick="window.open('table.php?market=market${number}', '_blank', 'width=1200,height=800')">
            Види ценовник
          </button>
        </div>`;
    }

    function renderMarkets(filterType = "all", searchTerm = "") {
      const container = document.getElementById("marketsContainer");
      let list = markets;
      if (filterType !== "all") list = list.filter((m) => m.type === filterType);
      if (searchTerm) {
        const term = searchTerm.toLowerCase();
        list = list.filter((m) => String(m.name).toLowerCase().includes(term) || String(m.city).toLowerCase().includes(term));
      }
      container.innerHTML = list.map(generateMarketCard).join("") || '<p class="card">Нема резултати.</p>';
    }

    document.addEventListener("DOMContentLoaded", function () {
      renderMarkets();
      const searchInput = document.getElementById("marketSearch");
      searchInput.addEventListener("input", function () {
        const active = document.querySelector(".filter-btn.active");
        renderMarkets(active ? active.dataset.filter : "all", this.value);
      });
      document.querySelectorAll(".filter-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
          document.querySelectorAll(".filter-btn").forEach((b) => b.classList.remove("active"));
          this.classList.add("active");
          renderMarkets(this.dataset.filter, searchInput.value);
        });
      });
    });
  </script>
<?php
$script = ob_get_clean();

site_header('Ценовници', 'cenovnik');
?>
  <main class="wrap">
    <section class="page-hero">
      <span class="kicker"><?= t('cenovnik.kicker') ?></span>
      <h1><?= t('cenovnik.title') ?> <em><?= t('cenovnik.title_em') ?></em></h1>
      <p><?= tn('cenovnik.text') ?></p>
    </section>

    <div class="search-box">
      <i class="fas fa-search"></i>
      <input type="text" id="marketSearch" placeholder="Пребарај по град или име...">
    </div>
    <div class="filters">
      <button class="filter-btn active" data-filter="all">Сите</button>
      <button class="filter-btn" data-filter="super">Hyper</button>
      <button class="filter-btn" data-filter="regular">Маркет</button>
    </div>

    <div class="market-buttons-grid" id="marketsContainer"></div>
  </main>
<?php
site_footer($script);
