<script setup lang="ts">
import { reactive, useAttrs, ref } from 'vue';
import { Errors } from '../feedbackIndicator';
import { MaskInput } from 'vue-3-mask';
import { ViewIcon, HideIcon } from '@adersolutions/icons';

interface TextInputProps {
    modelValue: string | number | null | undefined;
    prefix?: string | any;
    suffix?: string | any;
    label?: string;
    inputClass?: string;
    multiline?: number;
    name?: string;
    type?: string;
    error?: string | string[];
    placeholder?: string;
    disabled?: boolean;
    id?: string;
    autocomplete?: string;
    mask?: string;
    required?: boolean;
    avoidReplace?: boolean;
    labelClass?: string | undefined;
    helperText?: string | undefined;
    inputmode?: string;
    length?: number;
    autofocus?: boolean;
    suffixEvent?: boolean;
}

interface State {
    showPw: boolean;
}

const emit = defineEmits(['update:modelValue', 'click:suffix']);
const props = defineProps<TextInputProps>();

const compName = props.multiline ? 'textarea' : 'input';
const attrs = useAttrs();

// random unique id
const state = reactive<State>({
    showPw: false,
});
const uniqueID = ref(Math.random().toString(36).substring(7));

const onBlur = () => {
    if (props.mask && !props.avoidReplace) {
        emit('update:modelValue', props.modelValue?.toString().replaceAll(' ', '') || props.modelValue);
        return;
    }
};

const onInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (props.mask) {
        emit('update:modelValue', e);
        return;
    }
    if (props.length && target.value.length > props.length) {
        emit('update:modelValue', props.modelValue);
        return;
    }
    emit('update:modelValue', target.value);
};
</script>
<template>
    <div class="flex-1">
        <div class="relative w-full">
            <MaskInput
                v-if="mask"
                v-bind="attrs"
                :id="id || uniqueID"
                :mask="mask"
                :model-value="modelValue"
                :placeholder="placeholder"
                :value="modelValue"
                :type="type"
                :class="[inputClass || 'form-control peer', multiline && 'h-auto', suffix && 'pr-9']"
                @update:model-value="onInput"
                @blur="onBlur"
            />
            <component
                :is="compName"
                v-else
                v-bind="attrs"
                :id="id || uniqueID"
                placeholder=" "
                :type="type === 'password' ? (state.showPw ? 'text' : 'password') : type"
                :name="name || id || uniqueID"
                :disabled="disabled"
                :aria-invalid="!!error"
                :value="modelValue"
                :rows="multiline"
                :class="[
                    disabled && 'bg-gray-100 !text-gray-600',
                    inputClass || 'form-control peer appearance-none',
                    multiline && 'h-auto',
                    suffix && 'pr-9',
                    error && 'ring-2 ring-red-500 ',
                ]"
                :inputmode="inputmode"
                :autocomplete="autocomplete"
                :autofocus="autofocus"
                :maxlength="length"
                @input="onInput"
                @blur="onBlur"
            />
            <label
                :for="id || uniqueID"
                class="absolute form-label left-3 top-[3px] transition-all peer-placeholder-shown:top-3 peer-focus:top-[3px] peer-focus:text-blue-500 w-[90%] truncate"
            >
                {{ label }} <i v-if="placeholder && !modelValue">({{ placeholder }})</i>
                <span v-if="required" class="text-red-500">*</span>
            </label>
            <div class="absolute right-0 top-1/2 -translate-y-1/2 flex-center gap-2 text-gray-500">
                <button
                    v-if="type === 'password'"
                    type="button"
                    class="btn w-8 !px-0 flex-center text-gray-500"
                    @click="state.showPw = !state.showPw"
                >
                    <HideIcon v-if="state.showPw" class="h-5 w-5" />
                    <ViewIcon v-else class="h-5 w-5" />
                </button>

                <component
                    :is="suffix"
                    v-if="typeof suffix === 'function'"
                    :class="['h-8 w-8 p-1', suffixEvent && 'btn-m hover:bg-primary rounded-md text-primary hover:text-white']"
                    @click="$emit('click:suffix')"
                />
                <div v-else-if="suffix" class="px-2 font-bold text-sm">{{ suffix }}</div>
            </div>
        </div>
        <p v-if="helperText" class="text-sm text-gray-500">{{ helperText }}</p>
        <Errors v-if="error" :errors="error" />
    </div>
</template>
