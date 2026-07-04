import { defineAsyncComponent, type Component } from 'vue'

export function lazy(loader: () => Promise<{ default: Component }>) {
  return defineAsyncComponent({ loader, suspensible: false })
}
