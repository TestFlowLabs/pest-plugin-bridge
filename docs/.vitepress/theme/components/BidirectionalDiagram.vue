<script setup>
</script>

<template>
  <div class="diagram-container">
    <svg viewBox="0 0 600 280" class="bidirectional-diagram">
      <!-- Problem 1 Label -->
      <g class="problem-label">
        <text x="300" y="25" class="problem-title">Problem 1: Tests → Frontend</text>
        <text x="300" y="42" class="problem-desc">How does your test visit the frontend?</text>
      </g>

      <!-- Arrow down to boxes -->
      <g class="arrow-down">
        <line x1="300" y1="50" x2="300" y2="70" />
        <polygon points="295,65 300,75 305,65" />
      </g>

      <!-- Laravel API Box -->
      <g class="box api-box">
        <rect x="40" y="80" width="200" height="110" rx="8" />
        <text x="140" y="110" class="box-title">Laravel API</text>
        <text x="140" y="130" class="box-subtitle">(dynamic port)</text>
        <line x1="60" y1="145" x2="220" y2="145" class="divider" />
        <text x="140" y="165" class="box-route">/api/products</text>
        <text x="140" y="180" class="box-route">/api/cart</text>
      </g>

      <!-- Frontend Box -->
      <g class="box frontend-box">
        <rect x="360" y="80" width="200" height="110" rx="8" />
        <text x="460" y="110" class="box-title">Frontend App</text>
        <text x="460" y="130" class="box-subtitle">(localhost:3000)</text>
        <line x1="380" y1="145" x2="540" y2="145" class="divider" />
        <text x="460" y="165" class="box-route">/shop</text>
        <text x="460" y="180" class="box-route">/cart</text>
      </g>

      <!-- Bidirectional Arrow between boxes -->
      <g class="arrow-horizontal">
        <line x1="240" y1="135" x2="360" y2="135" />
        <polygon points="250,130 240,135 250,140" />
        <polygon points="350,130 360,135 350,140" />
      </g>

      <!-- Problem 2 Label -->
      <g class="problem-label-bottom">
        <line x1="140" y1="190" x2="140" y2="220" class="problem-line" />
        <text x="140" y="240" class="problem-title-small">Problem 2: Frontend → API</text>
        <text x="140" y="258" class="problem-desc-small">How does the frontend know</text>
        <text x="140" y="273" class="problem-desc-small">where the API is running?</text>
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

.bidirectional-diagram {
  width: 100%;
  max-width: 600px;
  height: auto;
  display: block;
  margin: 0 auto;
}

/* Problem labels */
.problem-title {
  font-family: var(--vp-font-family-base);
  font-size: 15px;
  font-weight: 600;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

.problem-desc {
  font-family: var(--vp-font-family-base);
  font-size: 13px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.problem-title-small {
  font-family: var(--vp-font-family-base);
  font-size: 13px;
  font-weight: 600;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

.problem-desc-small {
  font-family: var(--vp-font-family-base);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.problem-line {
  stroke: var(--diagram-line);
  stroke-width: 1;
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

.divider {
  stroke: var(--diagram-box-border);
  stroke-width: 1;
}

/* Arrows */
.arrow-down line,
.arrow-horizontal line {
  stroke: var(--diagram-arrow);
  stroke-width: 2;
}

.arrow-down polygon,
.arrow-horizontal polygon {
  fill: var(--diagram-arrow);
}

/* Responsive */
@media (max-width: 640px) {
  .diagram-container {
    padding: 12px;
  }
}
</style>
