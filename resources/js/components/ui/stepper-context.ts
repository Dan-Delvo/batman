import type { Ref } from 'vue'

export interface StepperContext {
  activeStep: Readonly<Ref<number>>
  setActiveStep: (step: number) => void
  totalSteps: Readonly<Ref<number>>
  orientation: 'horizontal' | 'vertical'
  setTotalSteps: (n: number) => void
}

export interface StepperItemContext {
  index: number
}

export const STEPPER_INJECTION_KEY = Symbol('stepper')
export const STEPPER_ITEM_INJECTION_KEY = Symbol('stepper-item')

