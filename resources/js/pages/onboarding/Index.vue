<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import Card from '@/components/ui/Card.vue';
import CardContent from '@/components/ui/CardContent.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import Input from '@/components/ui/Input.vue';
import { Label } from '@/components/ui/label';
import Stepper from '@/components/ui/Stepper.vue';
import StepperContent from '@/components/ui/StepperContent.vue';
import StepperItem from '@/components/ui/StepperItem.vue';
import StepperList from '@/components/ui/StepperList.vue';
import StepperSeparator from '@/components/ui/StepperSeparator.vue';
import StepperTrigger from '@/components/ui/StepperTrigger.vue';

const page = usePage();
const authUser = computed(() => page.props.auth.user);

const activeStep = ref(0);

const form = useForm<{
    name: string;
    req_hrs: string;
    company: string;
    supervisor: string;
}>({
    name: String(authUser.value?.name ?? ''),
    req_hrs: authUser.value?.req_hrs && authUser.value.req_hrs !== '0' ? String(authUser.value.req_hrs) : '',
    company: String(authUser.value?.company ?? ''),
    supervisor: String(authUser.value?.supervisor ?? ''),
});

const stepCount = 4;
const isLastStep = computed(() => activeStep.value === stepCount - 1);

const canContinue = computed(() => {
    switch (activeStep.value) {
        case 0:
            return form.name.trim().length > 0;
        case 1:
            return String(form.req_hrs).trim().length > 0;
        case 2:
            return form.company.trim().length > 0;
        case 3:
            return form.supervisor.trim().length > 0;
        default:
            return false;
    }
});

const goNext = () => {
    if (!canContinue.value || activeStep.value >= stepCount - 1) {
        return;
    }

    activeStep.value += 1;
};

const goBack = () => {
    if (activeStep.value === 0) {
        return;
    }

    activeStep.value -= 1;
};

const submit = () => {
    form.post('/onboarding');
};
</script>

<template>
    <Head title="Onboarding" />

    <div class="min-h-screen bg-background p-6 md:p-10">
        <div class="mx-auto max-w-4xl space-y-6">
            <div class="space-y-2">
                <h1 class="text-3xl font-black uppercase tracking-wide">Welcome To Your Internship Tracker</h1>
                <p class="text-muted-foreground">
                    Let&apos;s quickly set up your profile before you enter the dashboard.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Onboarding</CardTitle>
                </CardHeader>
                <CardContent class="space-y-8">
                    <Stepper v-model:active-step="activeStep" class="flex-col gap-6">
                        <StepperList class="w-full">
                            <StepperItem :index="0" class="flex-1">
                                <StepperTrigger />
                                <StepperSeparator />
                            </StepperItem>

                            <StepperItem :index="1" class="flex-1">
                                <StepperTrigger />
                                <StepperSeparator />
                            </StepperItem>

                            <StepperItem :index="2" class="flex-1">
                                <StepperTrigger />
                                <StepperSeparator />
                            </StepperItem>

                            <StepperItem :index="3" class="flex-1">
                                <StepperTrigger />
                            </StepperItem>
                        </StepperList>

                        <StepperContent :index="0" class="mt-0 space-y-3">
                            <Label for="name">Your Name</Label>
                            <Input id="name" v-model="form.name" placeholder="Juan Dela Cruz" />
                            <InputError :message="form.errors.name" />
                        </StepperContent>

                        <StepperContent :index="1" class="mt-0 space-y-3">
                            <Label for="req_hrs">Hours Needed</Label>
                            <Input id="req_hrs" v-model="form.req_hrs" type="number" min="1" placeholder="500" />
                            <InputError :message="form.errors.req_hrs" />
                        </StepperContent>

                        <StepperContent :index="2" class="mt-0 space-y-3">
                            <Label for="company">Internship Company</Label>
                            <Input id="company" v-model="form.company" placeholder="ACME Corporation" />
                            <InputError :message="form.errors.company" />
                        </StepperContent>

                        <StepperContent :index="3" class="mt-0 space-y-3">
                            <Label for="supervisor">Supervisor Name</Label>
                            <Input id="supervisor" v-model="form.supervisor" placeholder="Maria Santos" />
                            <InputError :message="form.errors.supervisor" />
                        </StepperContent>
                    </Stepper>

                    <div class="flex items-center justify-between gap-3 pt-2">
                        <Button variant="outline" :disabled="activeStep === 0 || form.processing" @click="goBack">
                            Back
                        </Button>

                        <Button
                            v-if="!isLastStep"
                            :disabled="!canContinue || form.processing"
                            @click="goNext"
                        >
                            Next
                        </Button>

                        <Button
                            v-else
                            :disabled="!canContinue || form.processing"
                            @click="submit"
                        >
                            Finish Setup
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
