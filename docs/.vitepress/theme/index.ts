import DefaultTheme from 'vitepress/theme'
import type { Theme } from 'vitepress'
import './style.css'

// Diagram components
import ArchitectureDiagram from './components/ArchitectureDiagram.vue'
import BidirectionalDiagram from './components/BidirectionalDiagram.vue'
import TestFlowDiagram from './components/TestFlowDiagram.vue'
import CIFlowDiagram from './components/CIFlowDiagram.vue'
import MemoryProblemDiagram from './components/MemoryProblemDiagram.vue'
import HomeFeatures from './components/HomeFeatures.vue'

export default {
  extends: DefaultTheme,
  enhanceApp({ app }) {
    // Register diagram components globally
    app.component('ArchitectureDiagram', ArchitectureDiagram)
    app.component('BidirectionalDiagram', BidirectionalDiagram)
    app.component('TestFlowDiagram', TestFlowDiagram)
    app.component('CIFlowDiagram', CIFlowDiagram)
    app.component('MemoryProblemDiagram', MemoryProblemDiagram)
    app.component('HomeFeatures', HomeFeatures)
  }
} satisfies Theme
