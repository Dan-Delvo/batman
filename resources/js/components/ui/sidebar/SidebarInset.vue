<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <main
    data-slot="sidebar-inset"
    :class="cn(
      'bg-background relative flex w-full flex-1 flex-col',
      'md:peer-data-[variant=inset]:m-3 md:peer-data-[variant=inset]:ml-0 md:peer-data-[variant=inset]:rounded-xl md:peer-data-[variant=inset]:border-3 md:peer-data-[variant=inset]:border-foreground md:peer-data-[variant=inset]:shadow-[6px_6px_0px_hsl(var(--shadow-color))] md:peer-data-[variant=inset]:peer-data-[state=collapsed]:ml-3',
      props.class,
    )"
  >
    <slot />
  </main>
</template>
