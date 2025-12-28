<script setup>
</script>

<template>
  <div class="diagram-container">
    <svg viewBox="0 0 500 380" class="testflow-diagram">
      <!-- Title -->
      <text x="250" y="25" class="diagram-title">TEST EXECUTION FLOW</text>

      <!-- Step 1: Laravel Backend -->
      <g class="step">
        <rect x="150" y="45" width="200" height="50" rx="6" class="step-box" />
        <text x="250" y="70" class="step-title">Laravel Backend</text>
        <text x="250" y="85" class="step-subtitle">(Port 8000)</text>
        <text x="380" y="70" class="step-note">← You run tests here</text>
      </g>

      <!-- Arrow 1 -->
      <g class="flow-arrow">
        <line x1="250" y1="95" x2="250" y2="125" />
        <polygon points="245,120 250,130 255,120" />
        <text x="260" y="115" class="flow-label">1. Pest runs tests</text>
      </g>

      <!-- Step 2: Playwright -->
      <g class="step">
        <rect x="150" y="130" width="200" height="50" rx="6" class="step-box" />
        <text x="250" y="155" class="step-title">Playwright Browser</text>
        <text x="250" y="170" class="step-subtitle">Automated</text>
        <text x="380" y="155" class="step-note">← Headless browser</text>
      </g>

      <!-- Arrow 2 -->
      <g class="flow-arrow">
        <line x1="250" y1="180" x2="250" y2="210" />
        <polygon points="245,205 250,215 255,205" />
        <text x="260" y="200" class="flow-label">2. Visits frontend URL</text>
      </g>

      <!-- Step 3: External Frontend -->
      <g class="step highlight">
        <rect x="150" y="215" width="200" height="50" rx="6" class="step-box-accent" />
        <text x="250" y="240" class="step-title-accent">External Frontend</text>
        <text x="250" y="255" class="step-subtitle-accent">(Port 3000)</text>
        <text x="380" y="240" class="step-note">← Vue/React/Nuxt...</text>
      </g>

      <!-- Arrow 3 -->
      <g class="flow-arrow">
        <line x1="250" y1="265" x2="250" y2="295" />
        <polygon points="245,290 250,300 255,290" />
        <text x="260" y="285" class="flow-label">3. Makes API requests</text>
      </g>

      <!-- Step 4: Laravel Backend (API) -->
      <g class="step">
        <rect x="150" y="300" width="200" height="50" rx="6" class="step-box" />
        <text x="250" y="325" class="step-title">Laravel Backend</text>
        <text x="250" y="340" class="step-subtitle">(Same instance)</text>
        <text x="380" y="325" class="step-note">← Serves API</text>
      </g>
    </svg>
  </div>
</template>

<style scoped>
.diagram-container {
  margin: 24px 0;
  padding: 24px;
  background: var(--diagram-bg);
  border: 1px solid var(--diagram-box-border);
  border-radius: 12px;
}

.testflow-diagram {
  width: 100%;
  max-width: 500px;
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

/* Steps */
.step-box {
  fill: var(--diagram-box-bg);
  stroke: var(--diagram-box-border);
  stroke-width: 1.5;
}

.step-box-accent {
  fill: var(--diagram-accent-light);
  stroke: var(--diagram-accent);
  stroke-width: 2;
}

.step-title {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 600;
  fill: var(--diagram-text);
  text-anchor: middle;
}

.step-title-accent {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 600;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

.step-subtitle {
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.step-subtitle-accent {
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
  fill: var(--diagram-accent);
  text-anchor: middle;
  opacity: 0.8;
}

.step-note {
  font-family: var(--vp-font-family-base);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: start;
}

/* Flow arrows */
.flow-arrow line {
  stroke: var(--diagram-arrow);
  stroke-width: 2;
}

.flow-arrow polygon {
  fill: var(--diagram-arrow);
}

.flow-label {
  font-family: var(--vp-font-family-base);
  font-size: 11px;
  fill: var(--diagram-text-muted);
  text-anchor: start;
}

/* Responsive */
@media (max-width: 640px) {
  .diagram-container {
    padding: 16px;
  }

  .step-note {
    display: none;
  }
}
</style>
