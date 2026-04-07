<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'
import { HTMLAttributes } from 'vue'

const paginationLinkVariants = cva(
  'inline-flex items-center justify-center whitespace-nowrap text-sm font-bold uppercase tracking-wide transition-all duration-200 border-3 border-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
  {
    variants: {
      size: {
        default: 'h-11 min-w-11 px-4',
        sm: 'h-9 min-w-9 px-3 text-xs',
      },
      active: {
        true: 'bg-primary text-primary-foreground shadow-[4px_4px_0px_hsl(var(--shadow-color))]',
        false: 'bg-background text-foreground shadow-[4px_4px_0px_hsl(var(--shadow-color))] hover:translate-x-[4px] hover:translate-y-[4px] hover:shadow-none active:translate-x-[4px] active:translate-y-[4px] active:shadow-none',
      },
    },
    defaultVariants: {
      size: 'default',
      active: false,
    },
  }
)

type PaginationLinkVariants = VariantProps<typeof paginationLinkVariants>

const props = defineProps<{
  class?: HTMLAttributes['class']
  href?: string
  isActive?: boolean
  size?: PaginationLinkVariants['size']
  only?: string[]
  preserveScroll?: boolean
  preserveState?: boolean
}>()
</script>

<template>
  <component
    :is="href ? Link : 'span'"
    :href="href"
    :only="href ? only : undefined"
    :preserve-scroll="href ? preserveScroll : undefined"
    :preserve-state="href ? preserveState : undefined"
    :class="cn(paginationLinkVariants({ size, active: isActive }), props.class)"
  >
    <slot />
  </component>
</template>