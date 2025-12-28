<script setup>
</script>

<template>
  <div class="diagram-container">
    <svg viewBox="0 0 580 160" class="http-faking-diagram">
      <!-- Test Process Box -->
      <g class="box test-box">
        <rect x="10" y="30" width="150" height="100" rx="8" class="step-box-accent" />
        <text x="85" y="58" class="box-title-accent">Test Process</text>
        <text x="85" y="78" class="box-code-accent">Bridge::fake()</text>
        <g class="status-badge success">
          <rect x="30" y="90" width="110" height="22" rx="4" class="badge-bg-success" />
          <text x="85" y="105" class="badge-text-success">✓ Writes</text>
        </g>
      </g>

      <!-- File Transfer Arrow -->
      <g class="file-arrow">
        <line x1="160" y1="80" x2="210" y2="80" />
        <polygon points="205,75 215,80 205,85" />
        <text x="185" y="65" class="file-label">file</text>
      </g>

      <!-- Laravel API Box -->
      <g class="box api-box">
        <rect x="215" y="30" width="150" height="100" rx="8" class="step-box-accent" />
        <text x="290" y="58" class="box-title-accent">Laravel API</text>
        <text x="290" y="78" class="box-subtitle">Middleware</text>
        <g class="status-badge success">
          <rect x="235" y="90" width="110" height="22" rx="4" class="badge-bg-success" />
          <text x="290" y="105" class="badge-text-success">✓ Reads</text>
        </g>
      </g>

      <!-- Blocked Arrow -->
      <g class="blocked-arrow">
        <line x1="365" y1="80" x2="415" y2="80" class="blocked-line" />
        <text x="390" y="65" class="blocked-x">✗</text>
      </g>

      <!-- Stripe API Box (blocked) -->
      <g class="box external-box">
        <rect x="420" y="30" width="150" height="100" rx="8" class="blocked-box" />
        <text x="495" y="58" class="box-title-blocked">Stripe API</text>
        <text x="495" y="78" class="box-subtitle-blocked">(blocked)</text>
        <g class="status-badge blocked">
          <rect x="440" y="90" width="110" height="22" rx="4" class="badge-bg-blocked" />
          <text x="495" y="105" class="badge-text-blocked">Faked response</text>
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

.http-faking-diagram {
  width: 100%;
  max-width: 580px;
  height: auto;
  display: block;
  margin: 0 auto;
}

/* Boxes */
.step-box-accent {
  fill: var(--diagram-accent-light);
  stroke: var(--diagram-accent);
  stroke-width: 1.5;
}

.blocked-box {
  fill: var(--diagram-box-bg);
  stroke: var(--diagram-box-border);
  stroke-width: 1.5;
  stroke-dasharray: 4 2;
  opacity: 0.6;
}

.box-title-accent {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 600;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

.box-title-blocked {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 600;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.box-subtitle {
  font-family: var(--vp-font-family-mono);
  font-size: 11px;
  fill: var(--diagram-accent);
  text-anchor: middle;
  opacity: 0.8;
}

.box-subtitle-blocked {
  font-family: var(--vp-font-family-mono);
  font-size: 11px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.box-code-accent {
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

/* Status badges */
.badge-bg-success {
  fill: rgba(34, 197, 94, 0.15);
  stroke: none;
}

.badge-text-success {
  font-family: var(--vp-font-family-base);
  font-size: 11px;
  font-weight: 500;
  fill: #22c55e;
  text-anchor: middle;
}

.badge-bg-blocked {
  fill: var(--diagram-box-bg);
  stroke: var(--diagram-box-border);
  stroke-width: 1;
}

.badge-text-blocked {
  font-family: var(--vp-font-family-base);
  font-size: 10px;
  font-weight: 500;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

/* File arrow */
.file-arrow line {
  stroke: var(--diagram-accent);
  stroke-width: 2;
}

.file-arrow polygon {
  fill: var(--diagram-accent);
}

.file-label {
  font-family: var(--vp-font-family-mono);
  font-size: 11px;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

/* Blocked arrow */
.blocked-line {
  stroke: var(--diagram-line);
  stroke-width: 2;
  stroke-dasharray: 6 4;
}

.blocked-x {
  font-family: var(--vp-font-family-base);
  font-size: 16px;
  font-weight: 700;
  fill: #ef4444;
  text-anchor: middle;
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
