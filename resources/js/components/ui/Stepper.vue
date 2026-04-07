<script setup lang="ts">
import { provide, ref, computed, readonly } from 'vue'
import { cn } from '@/lib/utils'
import { STEPPER_INJECTION_KEY } from './stepper-context'

interface StepperProps {
  activeStep?: number
  orientation?: 'horizontal' | 'vertical'
  class?: string
}

const props = withDefaults(defineProps<StepperProps>(), {
  activeStep: 0,
  orientation: 'horizontal',
})

const emit = defineEmits<{
  'update:activeStep': [step: number]
  stepChange: [step: number]
}>()

const uncontrolledActiveStep = ref(0)
const totalSteps = ref(0)

const isControlled = computed(() => props.activeStep !== undefined)
const currentActiveStep = computed(() => isControlled.value ? props.activeStep! : uncontrolledActiveStep.value)

const setActiveStep = (step: number) => {
  if (!isControlled.value) {
    uncontrolledActiveStep.value = step
  }
  emit('update:activeStep', step)
  emit('stepChange', step)
}

const setTotalSteps = (count: number) => {
  totalSteps.value = count
}

provide(STEPPER_INJECTION_KEY, {
  activeStep: readonly(computed(() => currentActiveStep.value)),
  setActiveStep,
  totalSteps: readonly(totalSteps),
  orientation: props.orientation,
  setTotalSteps,
})
</script>

<template>
  <div
    :class="cn(
      'flex',
      orientation === 'horizontal' ? 'flex-row' : 'flex-col',
      props.class
    )"
  >
    <slot />
  </div>
</template>
