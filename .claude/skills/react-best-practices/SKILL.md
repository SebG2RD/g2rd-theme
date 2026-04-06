---
name: react-best-practices
description: React and Next.js performance optimization expert. Covers eliminating waterfalls, bundle size reduction, server-side performance, client-side data fetching, re-render optimization, rendering performance, JavaScript micro-optimizations, and advanced patterns. Use when optimizing React/Next.js apps, reviewing components for performance, implementing Gutenberg blocks with React, or debugging slow rendering/loading issues.
license: MIT
metadata:
  author: Vercel Engineering
  version: "0.1.0"
  domain: frontend
  triggers: React, Next.js, performance, useMemo, useCallback, bundle size, waterfall, re-render, Gutenberg block optimization
  role: expert
  scope: implementation
  output-format: code
  related-skills: senior-frontend, wordpress-pro
---

# React Best Practices - Performance Optimization

Comprehensive performance optimization guide for React and Next.js applications with 40+ rules organized by impact level.

## Categories overview

### 1. Eliminating Waterfalls (CRITICAL)
- Defer await until needed
- Use `Promise.all()` for independent operations
- Prevent waterfall chains in API routes
- Strategic Suspense boundaries

```typescript
// ✅ Parallel fetching
const [user, posts, comments] = await Promise.all([
  fetchUser(),
  fetchPosts(),
  fetchComments()
])
```

### 2. Bundle Size Optimization (CRITICAL)
- Avoid barrel file imports — import directly from source
- Dynamic imports for heavy components
- Conditional module loading

```tsx
// ❌ Loads entire library
import { Check } from 'lucide-react'
// ✅ Loads only what you need
import Check from 'lucide-react/dist/esm/icons/check'

// Dynamic import
const MonacoEditor = dynamic(() => import('./monaco-editor'), { ssr: false })
```

### 3. Server-Side Performance (HIGH)
- Cross-request LRU caching
- Minimize serialization at RSC boundaries
- Per-request deduplication with `React.cache()`

### 4. Re-render Optimization (MEDIUM)
- Wrap expensive computations in `useMemo`
- Wrap stable callbacks in `useCallback`
- Hoist static objects/arrays outside components (module level)
- Narrow effect dependencies
- Use lazy state initialization

```tsx
// ❌ Recreated every render
const ICON_CATEGORIES = { ... };
function MyComponent() { ... }

// ✅ Hoisted at module level
const ICON_CATEGORIES = { ... };
const LAYOUT_OPTIONS = [ ... ];
function MyComponent() { ... }

// ✅ useMemo for expensive computation
const derived = useMemo(() => {
  if (!Array.isArray(data)) return {};
  return data.reduce((acc, item) => { ... }, {});
}, [data]);

// ✅ useCallback for stable function refs
const handleSelect = useCallback((items) => {
  setAttributes({ items });
}, [setAttributes]);
```

### 5. Rendering Performance (MEDIUM)
- CSS `content-visibility` for long lists
- Hoist static JSX elements
- Use explicit conditional rendering (avoid `&&` with non-boolean left side)

### 6. JavaScript Performance (LOW-MEDIUM)
- Use `Set`/`Map` for O(1) lookups
- Use `toSorted()` instead of mutating `.sort()`
- Cache repeated function calls
- Hoist RegExp creation outside render

### 7. Advanced Patterns (LOW)
- Store event handlers in refs for stable callbacks
- `useLatest` for stable callback refs

## Gutenberg-specific rules

- `useState(null)` → TypeScript infers `null`; use `Array.isArray()` to narrow before `.reduce()`
- Hoist `BLOCK_SUPPORTS`, `ICON_LIST`, option arrays outside the component
- `useCallback([setAttributes])` for `onSelect*` handlers passed to `MediaUpload`
- `useMemo` for derived data from block attributes or REST responses
- Prefer `useEffect` with proper deps over inline computations in render

## Key metrics to track

- Time to Interactive (TTI)
- Largest Contentful Paint (LCP)
- Bundle size (initial JS payload)
- React DevTools Profiler — wasted renders
