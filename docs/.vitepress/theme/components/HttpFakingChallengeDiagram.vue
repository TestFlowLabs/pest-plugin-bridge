<script setup>
</script>

<template>
  <div class="diagram-container">
    <svg viewBox="0 0 580 140" class="http-faking-diagram">
      <!-- Test Process Box -->
      <g class="box test-box">
        <rect x="10" y="20" width="150" height="100" rx="8" class="step-box" />
        <text x="85" y="50" class="box-title">Test Process</text>
        <text x="85" y="72" class="box-code">Http::fake()</text>
        <g class="status-badge error">
          <rect x="30" y="82" width="110" height="22" rx="4" class="badge-bg" />
          <text x="85" y="97" class="badge-text">✗ Not shared</text>
        </g>
      </g>

      <!-- Arrow 1 -->
      <g class="arrow">
        <line x1="160" y1="70" x2="210" y2="70" />
        <polygon points="205,65 215,70 205,75" />
      </g>

      <!-- Laravel API Box -->
      <g class="box api-box">
        <rect x="215" y="20" width="150" height="100" rx="8" class="step-box" />
        <text x="290" y="50" class="box-title">Laravel API</text>
        <text x="290" y="72" class="box-subtitle">(separate)</text>
        <g class="status-badge error">
          <rect x="235" y="82" width="110" height="22" rx="4" class="badge-bg" />
          <text x="290" y="97" class="badge-text">✗ No fake</text>
        </g>
      </g>

      <!-- Arrow 2 -->
      <g class="arrow">
        <line x1="365" y1="70" x2="415" y2="70" />
        <polygon points="410,65 420,70 410,75" />
      </g>

      <!-- Stripe API Box -->
      <g class="box external-box">
        <rect x="420" y="20" width="150" height="100" rx="8" class="external-step-box" />
        <text x="495" y="50" class="box-title-warning">Stripe API</text>
        <text x="495" y="72" class="box-subtitle">(external)</text>
        <text x="495" y="95" class="box-warning">Real calls!</text>
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

.http-faking-diagram {
  width: 100%;
  max-width: 580px;
  height: auto;
  display: block;
  margin: 0 auto;
}

/* Boxes */
.step-box {
  fill: var(--diagram-box-bg);
  stroke: var(--diagram-box-border);
  stroke-width: 1.5;
}

.external-step-box {
  fill: rgba(245, 158, 11, 0.08);
  stroke: #f59e0b;
  stroke-width: 1.5;
  stroke-dasharray: 4 2;
}

.box-title {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 600;
  fill: var(--diagram-text);
  text-anchor: middle;
}

.box-title-warning {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 600;
  fill: #f59e0b;
  text-anchor: middle;
}

.box-subtitle {
  font-family: var(--vp-font-family-mono);
  font-size: 11px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.box-code {
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.box-warning {
  font-family: var(--vp-font-family-base);
  font-size: 11px;
  font-weight: 500;
  fill: #f59e0b;
  text-anchor: middle;
}

/* Status badges */
.badge-bg {
  fill: rgba(239, 68, 68, 0.1);
  stroke: none;
}

.badge-text {
  font-family: var(--vp-font-family-base);
  font-size: 11px;
  font-weight: 500;
  fill: #ef4444;
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

/* Responsive */
@media (max-width: 640px) {
  .diagram-container {
    padding: 12px;
    overflow-x: auto;
  }

  .http-faking-diagram {
    min-width: 500px;
  }
}
</style>
