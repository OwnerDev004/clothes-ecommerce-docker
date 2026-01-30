<template>
    <div class="dual-range-slider">
      <div class="slider-container">
        <label>Min Value</label>
        <input
          type="range"
          :min="minLimit"
          :max="max"
          v-model="minValue"
          @input="updateMinValue"
          class="slider"
        />
        <div class="value-display">{{ minValue }}</div>
      </div>
  
      <div class="slider-container">
        <label>Max Value</label>
        <input
          type="range"
          :min="min"
          :max="maxLimit"
          v-model="maxValue"
          @input="updateMaxValue"
          class="slider"
        />
        <div class="value-display">{{ maxValue }}</div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, watch } from 'vue'
  
  const props = defineProps({
    minLimit: {
      type: Number,
      default: 0,
    },
    maxLimit: {
      type: Number,
      default: 100,
    },
    min: {
      type: Number,
      default: 25,
    },
    max: {
      type: Number,
      default: 75,
    },
  })
  
  const emit = defineEmits(['update:min', 'update:max'])
  
  const minValue = ref(props.min)
  const maxValue = ref(props.max)
  
  const updateMinValue = () => emit('update:min', minValue.value)
  const updateMaxValue = () => emit('update:max', maxValue.value)
  
  watch(
    () => props.min,
    (newVal) => {
      if (newVal !== minValue.value) minValue.value = newVal
    }
  )
  
  watch(
    () => props.max,
    (newVal) => {
      if (newVal !== maxValue.value) maxValue.value = newVal
    }
  )
  </script>
  
  <style scoped>
  .dual-range-slider {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }
  
  .slider-container {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .slider {
    appearance: none;
    width: 100%;
    height: 4px;
    background: #ddd;
    border-radius: 5px;
    outline: none;
    cursor: pointer;
  }
  
  .slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    background: #4caf50;
    border-radius: 50%;
  }
  
  .slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: #4caf50;
    border-radius: 50%;
  }
  
  .value-display {
    font-size: 14px;
    color: #333;
  }
  </style>
  