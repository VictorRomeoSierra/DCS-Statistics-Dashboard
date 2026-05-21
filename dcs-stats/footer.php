<footer>
  <p>&copy; 2025 DCS Statistics Dashboard | <button type="button" class="credits-link" id="openCredits">Credits</button></p>
</footer>

<div class="credits-modal" id="creditsModal" aria-hidden="true">
  <div class="credits-box" role="dialog" aria-modal="true" aria-labelledby="creditsTitle">
    <button type="button" class="credits-close" id="closeCredits" aria-label="Close credits">&times;</button>
    <h2 id="creditsTitle">Credits</h2>
    <div class="credits-list">
      <a href="https://skypirates.uk" target="_blank" rel="noopener noreferrer">VFS-252 Sky Pirates</a>
      <a href="https://503rdblacksheep.com" target="_blank" rel="noopener noreferrer">{503rd} Blacksheep</a>
      <a href="https://github.com/Special-K-s-Flightsim-Bots" target="_blank" rel="noopener noreferrer">Special K</a>
    </div>
  </div>
</div>

<style>
  .credits-link {
    background: none;
    border: 0;
    color: #4CAF50;
    cursor: pointer;
    font: inherit;
    padding: 0;
    text-decoration: underline;
    text-underline-offset: 3px;
  }

  .credits-link:hover,
  .credits-link:focus {
    color: #7ad77d;
    outline: none;
  }

  .credits-modal {
    align-items: center;
    background: rgba(0, 0, 0, 0.68);
    display: none;
    inset: 0;
    justify-content: center;
    padding: 20px;
    position: fixed;
    z-index: 1000;
  }

  .credits-modal.is-open {
    display: flex;
  }

  .credits-box {
    background: linear-gradient(135deg, #2c2c2c 0%, #1e1e1e 100%);
    border: 1px solid rgba(76, 175, 80, 0.35);
    border-radius: 8px;
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.45);
    color: #e0e0e0;
    max-width: 420px;
    padding: 24px;
    position: relative;
    text-align: left;
    width: min(100%, 420px);
  }

  .credits-box h2 {
    color: #4CAF50;
    margin: 0 36px 18px 0;
  }

  .credits-list {
    display: grid;
    gap: 12px;
  }

  .credits-list a {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    color: #e0e0e0;
    padding: 12px 14px;
    text-decoration: none;
  }

  .credits-list a:hover,
  .credits-list a:focus {
    border-color: rgba(76, 175, 80, 0.55);
    color: #4CAF50;
    outline: none;
  }

  .credits-close {
    align-items: center;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
    display: flex;
    font-size: 1.5rem;
    height: 34px;
    justify-content: center;
    line-height: 1;
    position: absolute;
    right: 16px;
    top: 16px;
    width: 34px;
  }

  .credits-close:hover,
  .credits-close:focus {
    border-color: rgba(76, 175, 80, 0.55);
    color: #4CAF50;
    outline: none;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('creditsModal');
    const openButton = document.getElementById('openCredits');
    const closeButton = document.getElementById('closeCredits');

    if (!modal || !openButton || !closeButton) return;

    const openCredits = () => {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      closeButton.focus();
    };

    const closeCredits = () => {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      openButton.focus();
    };

    openButton.addEventListener('click', openCredits);
    closeButton.addEventListener('click', closeCredits);
    modal.addEventListener('click', event => {
      if (event.target === modal) {
        closeCredits();
      }
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeCredits();
      }
    });
  });
</script>
</body>
</html>
