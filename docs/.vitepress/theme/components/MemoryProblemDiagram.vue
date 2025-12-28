<script setup>
</script>

<template>
  <div class="diagram-container">
    <svg viewBox="0 0 480 180" class="memory-diagram">
      <!-- Title -->
      <text x="240" y="20" class="diagram-title">:memory: Problem</text>

      <!-- Test Process Box -->
      <g class="process test-process">
        <text x="120" y="50" class="process-label">Test Process</text>
        <rect x="40" y="60" width="160" height="80" rx="6" class="process-box" />
        <text x="120" y="85" class="box-title">Connection A</text>
        <text x="120" y="105" class="box-mono">:memory: DB</text>
        <text x="120" y="130" class="box-action">User::create</text>
      </g>

      <!-- API Process Box -->
      <g class="process api-process">
        <text x="360" y="50" class="process-label">API Process</text>
        <rect x="280" y="60" width="160" height="80" rx="6" class="process-box" />
        <text x="360" y="85" class="box-title">Connection B</text>
        <text x="360" y="105" class="box-mono">:memory: DB</text>
        <text x="360" y="130" class="box-action">User::find</text>
      </g>

      <!-- Problem indicators -->
      <g class="problem-indicators">
        <text x="460" y="105" class="problem-note">← Different DB!</text>
        <text x="460" y="130" class="problem-note">← Can't see data</text>
      </g>

      <!-- X mark between boxes -->
      <g class="no-connection">
        <line x1="210" y1="90" x2="270" y2="110" class="x-mark" />
        <line x1="210" y1="110" x2="270" y2="90" class="x-mark" />
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

.memory-diagram {
  width: 100%;
  max-width: 480px;
  height: auto;
  display: block;
  margin: 0 auto;
}

.diagram-title {
  font-family: var(--vp-font-family-mono);
  font-size: 14px;
  font-weight: 600;
  fill: var(--diagram-accent);
  text-anchor: middle;
}

/* Process boxes */
.process-label {
  font-family: var(--vp-font-family-base);
  font-size: 12px;
  font-weight: 500;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.process-box {
  fill: var(--diagram-box-bg);
  stroke: var(--diagram-box-border);
  stroke-width: 1.5;
}

.box-title {
  font-family: var(--vp-font-family-base);
  font-size: 13px;
  font-weight: 600;
  fill: var(--diagram-text);
  text-anchor: middle;
}

.box-mono {
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

.box-action {
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
  fill: var(--diagram-text-muted);
  text-anchor: middle;
}

/* Problem notes */
.problem-note {
  font-family: var(--vp-font-family-base);
  font-size: 11px;
  fill: var(--diagram-accent);
  text-anchor: start;
}

/* X mark */
.x-mark {
  stroke: var(--diagram-accent);
  stroke-width: 2;
  stroke-linecap: round;
}

/* Responsive */
@media (max-width: 520px) {
  .problem-note {
    display: none;
  }
}
</style>
