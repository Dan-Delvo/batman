<script lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button/'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'

const isOpen = ref(false)
const selectedDate = ref(null)

const openDialog = (date) => {
  selectedDate.value = date
  isOpen.value = true
}
</script>

<template>
  <div class="p-8 max-w-7xl mx-auto space-y-6 bg-background text-foreground">
    <div class="flex items-center justify-between">
      <div class="space-y-1">
        <h2 class="text-3xl font-bold tracking-tight">Internship Log</h2>
        <p class="text-muted-foreground text-sm">Manage your daily tasks and generate weekly reports.</p>
      </div>
      <div class="flex items-center space-x-2">
        <Button variant="outline">Previous Month</Button>
        <Button>Next Month</Button>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card class="p-6">
        <div class="text-sm font-medium text-muted-foreground">Total Hours</div>
        <div class="text-2xl font-bold">128.5 / 486</div>
      </Card>
      </div>

    <Card class="p-6">
      <div class="grid grid-cols-7 gap-px bg-muted rounded-lg border overflow-hidden">
        <div v-for="day in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']" 
             class="bg-background p-3 text-center text-xs font-medium text-muted-foreground uppercase">
          {{ day }}
        </div>

        <div v-for="date in 30" :key="date"
             @click="openDialog(date)"
             class="bg-background min-h-[120px] p-3 hover:bg-accent transition-colors cursor-pointer group relative">
          
          <span class="text-sm font-medium" :class="date === 7 ? 'text-primary' : 'text-muted-foreground'">
            {{ date }}
          </span>

          <div v-if="date < 7" class="mt-2 space-y-1">
            <div class="text-[10px] bg-secondary text-secondary-foreground px-2 py-0.5 rounded-md truncate">
              UI Development...
            </div>
            <div class="text-[10px] font-bold text-primary">8.0 hrs</div>
          </div>
        </div>
      </div>
    </Card>

    <Dialog v-model:open="isOpen">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Daily Log: April {{ selectedDate }}</DialogTitle>
          <DialogDescription>Record your time and accomplishments for today.</DialogDescription>
        </DialogHeader>
        
        <div class="grid gap-4 py-4">
          <div class="grid grid-cols-4 items-center gap-4">
            <Label class="text-right">Time In</Label>
            <Input type="time" class="col-span-3" />
          </div>
          <div class="grid grid-cols-4 items-center gap-4">
            <Label class="text-right">Tasks</Label>
            <Textarea placeholder="What did you build today?" class="col-span-3" />
          </div>
        </div>

        <DialogFooter>
          <Button type="submit">Save Log</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

