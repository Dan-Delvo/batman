<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next'
import PaginationLink from './PaginationLink.vue'
import { cn } from '@/lib/utils'
import { HTMLAttributes } from 'vue'

defineProps<{
  class?: HTMLAttributes['class']
  href?: string
  only?: string[]
  preserveScroll?: boolean
  preserveState?: boolean
}>()
</script>

<template>
  <PaginationLink
    aria-label="Go to next page"
    size="default"
    :href="href"
    :only="only"
    :preserve-scroll="preserveScroll"
    :preserve-state="preserveState"
    :class="cn('gap-1 pr-2.5', $props.class)"
  >
    <span>Next</span>
    <ChevronRight class="h-4 w-4 stroke-3" />
  </PaginationLink>
</template>
