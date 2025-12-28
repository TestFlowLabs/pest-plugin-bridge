<script setup>
</script>

<template>
  <div class="diagram-container">
    <svg viewBox="0 0 520 300" class="ci-diagram">
      <!-- Title -->
      <text x="260" y="20" class="diagram-title">CI EXECUTION FLOW</text>

      <!-- Setup Phase Box -->
      <g class="phase setup-phase">
        <rect x="30" y="35" width="460" height="100" rx="8" class="phase-box" />
        <text x="260" y="55" class="phase-title">GitHub Actions Runner</text>

        <g class="step-list">
          <text x="50" y="75" class="step-item">1. Checkout code (API + Frontend)</text>
          <text x="50" y="90" class="step-item">2. Install PHP + Composer dependencies</text>
          <text x="50" y="105" class="step-item">3. Install Node.js + Playwright browsers</text>
          <text x="50" y="120" class="step-item">4. Install frontend dependencies</text>
        </g>
      </g>

      <!-- Arrow -->
      <g class="flow-arrow">
        <line x1="260" y1="135" x2="260" y2="165" />
        <polygon points="255,160 260,170 265,160" />
      </g>

      <!-- Test Phase Box -->
      <g class="phase test-phase">
        <rect x="30" y="170" width="460" height="110" rx="8" class="phase-box-accent" />
        <text x="260" y="195" class="phase-title-accent">./vendor/bin/pest tests/Browser</text>

        <g class="result-list">
          <circle cx="55" cy="218" r="4" class="dot dot-accent" />
          <text x="70" y="222" class="result-item">Laravel API starts automatically (in-process)</text>

          <circle cx="55" cy="238" r="4" class="dot dot-accent" />
          <text x="70" y="242" class="result-item">Frontend dev server starts via serve()</text>

          <circle cx="55" cy="258" r="4" class="dot dot-accent" />
          <text x="70" y="262" class="result-item">Playwright runs tests headlessly</text>
        </g>
      </g>
    </svg>
  </div>
</template>

<style scoped>
.diagram-container {
  margin: 24px 0;
  padding: 20px;
  background: var(--diagram-bg);
  border: 1px solid var(--diagram-box-border);
  border-radius: 12px;
}

.ci-diagram {
  width: 100%;
  max-width: 520px;
  height: auto;
  display: block;
  margin: 0 auto;
}

.diagram-title {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 700;
  fill: var(--diagram-text);
  text-anchor: middle;
  letter-spacing: 1px;
}

/* Phase boxes */
.phase-box {
  fill: var(--diagram-box-bg);
  stroke: var(--diagram-box-border);
  stroke-width: 1.5;
}

.phase-box-accent {
  fill: var(--diagram-accent-light);
  stroke: var(--diagram-accent);
  stroke-width: 2;
}

.phase-title {
  font-family: var(--vp-font-family-base);
  font-size: 13px;
  font-weight: 600;
  fill: var(--diagram-text);
  text-anchor: middle;
}

.phase-title-accent {
  font-family: var(--vp-font-family-mono);
  font-size: 13px;
  font-weight: 600;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

.step-item {
  font-family: var(--vp-font-family-base);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: start;
}

.result-item {
  font-family: var(--vp-font-family-base);
  font-size: 12px;
  fill: var(--diagram-text);
  text-anchor: start;
}

.dot {
  fill: var(--diagram-line);
}

.dot-accent {
  fill: var(--diagram-accent);
}

/* Arrow */
.flow-arrow line {
  stroke: var(--diagram-arrow);
  stroke-width: 2;
}

.flow-arrow polygon {
  fill: var(--diagram-arrow);
}

/* Responsive */
@media (max-width: 560px) {
  .diagram-container {
    padding: 12px;
    overflow-x: auto;
  }
}
</style>
