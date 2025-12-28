<script setup>
</script>

<template>
  <div class="diagram-container">
    <svg viewBox="0 0 460 280" class="visual-regression-diagram">
      <!-- Baseline Box -->
      <g class="box">
        <rect x="30" y="30" width="140" height="70" rx="8" class="step-box" />
        <text x="100" y="60" class="box-title">Baseline</text>
        <text x="100" y="80" class="box-subtitle">(expected)</text>
      </g>

      <!-- Current Box -->
      <g class="box">
        <rect x="290" y="30" width="140" height="70" rx="8" class="step-box" />
        <text x="360" y="60" class="box-title">Current</text>
        <text x="360" y="80" class="box-subtitle">(actual)</text>
      </g>

      <!-- Compare Arrow (bidirectional) -->
      <g class="compare-arrow">
        <line x1="170" y1="65" x2="290" y2="65" />
        <polygon points="175,60 165,65 175,70" />
        <polygon points="285,60 295,65 285,70" />
        <text x="230" y="55" class="arrow-label">Compare</text>
      </g>

      <!-- Flow lines from boxes to decision -->
      <g class="flow-lines">
        <line x1="100" y1="100" x2="100" y2="130" />
        <line x1="360" y1="100" x2="360" y2="130" />
        <line x1="100" y1="130" x2="230" y2="130" />
        <line x1="360" y1="130" x2="230" y2="130" />
        <line x1="230" y1="130" x2="230" y2="150" />
        <polygon points="225,145 230,155 235,145" class="flow-arrow" />
      </g>

      <!-- Decision Diamond -->
      <g class="decision">
        <polygon points="230,160 280,190 230,220 180,190" class="decision-box" />
        <text x="230" y="195" class="decision-text">Match?</text>
      </g>

      <!-- Pass Branch -->
      <g class="result pass">
        <line x1="180" y1="190" x2="100" y2="190" />
        <line x1="100" y1="190" x2="100" y2="240" />
        <circle cx="100" cy="250" r="18" class="result-circle pass-circle" />
        <text x="100" y="256" class="result-icon">✓</text>
        <text x="100" y="278" class="result-label">Pass</text>
      </g>

      <!-- Fail Branch -->
      <g class="result fail">
        <line x1="280" y1="190" x2="360" y2="190" />
        <line x1="360" y1="190" x2="360" y2="240" />
        <circle cx="360" cy="250" r="18" class="result-circle fail-circle" />
        <text x="360" y="256" class="result-icon">✗</text>
        <text x="360" y="278" class="result-label">Fail</text>
      </g>

      <!-- Diff note -->
      <text x="360" y="270" class="diff-note">(diff generated)</text>
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

.visual-regression-diagram {
  width: 100%;
  max-width: 460px;
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

/* Compare arrow */
.compare-arrow line {
  stroke: var(--diagram-accent);
  stroke-width: 2;
}

.compare-arrow polygon {
  fill: var(--diagram-accent);
}

.arrow-label {
  font-family: var(--vp-font-family-base);
  font-size: 13px;
  font-weight: 500;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

/* Flow lines */
.flow-lines line {
  stroke: var(--diagram-line);
  stroke-width: 1.5;
}

.flow-arrow {
  fill: var(--diagram-arrow);
}

/* Decision diamond */
.decision-box {
  fill: var(--diagram-accent-light);
  stroke: var(--diagram-accent);
  stroke-width: 2;
}

.decision-text {
  font-family: var(--vp-font-family-base);
  font-size: 14px;
  font-weight: 600;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

/* Result branches */
.result line {
  stroke: var(--diagram-line);
  stroke-width: 1.5;
}

.result-circle {
  stroke-width: 2;
}

.pass-circle {
  fill: rgba(34, 197, 94, 0.15);
  stroke: #22c55e;
}

.fail-circle {
  fill: rgba(239, 68, 68, 0.15);
  stroke: #ef4444;
}

.result-icon {
  font-family: var(--vp-font-family-base);
  font-size: 18px;
  font-weight: 700;
  text-anchor: middle;
}

.pass .result-icon {
  fill: #22c55e;
}

.fail .result-icon {
  fill: #ef4444;
}

.result-label {
  font-family: var(--vp-font-family-base);
  font-size: 13px;
  font-weight: 600;
  text-anchor: middle;
}

.pass .result-label {
  fill: #22c55e;
}

.fail .result-label {
  fill: #ef4444;
}

.diff-note {
  font-family: var(--vp-font-family-mono);
  font-size: 10px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
  transform: translateY(8px);
}

/* Responsive */
@media (max-width: 640px) {
  .diagram-container {
    padding: 16px;
  }
}
</style>
