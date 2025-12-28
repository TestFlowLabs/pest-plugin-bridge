<script setup>
</script>

<template>
  <div class="diagram-container">
    <svg viewBox="0 0 600 200" class="architecture-diagram">
      <!-- Laravel API Box -->
      <g class="box api-box">
        <rect x="20" y="40" width="160" height="120" rx="8" />
        <text x="100" y="75" class="box-title">Laravel API</text>
        <text x="100" y="100" class="box-subtitle">(dynamic port)</text>
        <text x="100" y="130" class="box-route">/api/users</text>
        <text x="100" y="150" class="box-route">/api/products</text>
      </g>

      <!-- Frontend Box -->
      <g class="box frontend-box">
        <rect x="420" y="40" width="160" height="120" rx="8" />
        <text x="500" y="75" class="box-title">Frontend</text>
        <text x="500" y="100" class="box-subtitle">(localhost:3000)</text>
        <text x="500" y="130" class="box-route">/login</text>
        <text x="500" y="150" class="box-route">/dashboard</text>
      </g>

      <!-- Plugin Bridge (center) -->
      <g class="plugin-bridge">
        <rect x="240" y="70" width="120" height="60" rx="6" class="plugin-box" />
        <text x="300" y="95" class="plugin-title">Plugin</text>
        <text x="300" y="115" class="plugin-subtitle">Bridge</text>
      </g>

      <!-- Arrow: API to Plugin (auto-injected) -->
      <g class="arrow arrow-left">
        <line x1="180" y1="85" x2="240" y2="85" />
        <polygon points="235,80 245,85 235,90" />
        <text x="210" y="75" class="arrow-label">API URL</text>
      </g>

      <!-- Arrow: Plugin to Frontend (bridge) -->
      <g class="arrow arrow-right">
        <line x1="360" y1="115" x2="420" y2="115" />
        <polygon points="415,110 425,115 415,120" />
        <text x="390" y="140" class="arrow-label">bridge()</text>
      </g>

      <!-- Bidirectional indicator -->
      <g class="bidirectional">
        <path d="M 180 100 Q 210 130 240 100" class="curve-line" />
        <path d="M 360 100 Q 390 130 420 100" class="curve-line" />
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

.architecture-diagram {
  width: 100%;
  max-width: 600px;
  height: auto;
  display: block;
  margin: 0 auto;
}

/* Boxes */
.box rect {
  fill: var(--diagram-box-bg);
  stroke: var(--diagram-box-border);
  stroke-width: 1.5;
}

.box-title {
  font-family: var(--vp-font-family-base);
  font-size: 15px;
  font-weight: 600;
  fill: var(--diagram-text);
  text-anchor: middle;
}

.box-subtitle {
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.box-route {
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

/* Plugin Bridge */
.plugin-box {
  fill: var(--diagram-accent-light);
  stroke: var(--diagram-accent);
  stroke-width: 2;
}

.plugin-title {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 600;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

.plugin-subtitle {
  font-family: var(--vp-font-family-base);
  font-size: 13px;
  font-weight: 500;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

/* Arrows */
.arrow line {
  stroke: var(--diagram-arrow);
  stroke-width: 2;
}

.arrow polygon {
  fill: var(--diagram-arrow);
}

.arrow-label {
  font-family: var(--vp-font-family-mono);
  font-size: 11px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

/* Curve lines */
.curve-line {
  fill: none;
  stroke: var(--diagram-line);
  stroke-width: 1;
  stroke-dasharray: 4 2;
}

/* Responsive */
@media (max-width: 640px) {
  .diagram-container {
    padding: 12px;
  }
}
</style>
