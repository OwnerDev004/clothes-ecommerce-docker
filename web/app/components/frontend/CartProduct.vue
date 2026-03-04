<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = defineProps({
   variantId: {
    type: Number,
    required: true
   },
   name:{
     type:String,
    default: ''
   },
   size:{
    type:String,
    default: ''
   },
   color:{
    type:String,
    default: ''
   },
   price:{
     type: Number,
     default: 0
   },
   quantity: {
    type: Number,
    default: 1
   },
   img:{
    type: String,
    default: ''
   }
})
const qtyAmount = ref(1);

// increment
const increment = () => {
  qtyAmount.value += 1;
  emit('update-quantity', { variantId: props.variantId, quantity: qtyAmount.value });
};
// decrement
const decrement = () => {
  if (qtyAmount.value > 1) {
    qtyAmount.value -= 1;
    emit('update-quantity', { variantId: props.variantId, quantity: qtyAmount.value });
  }
};

const emit = defineEmits(['remove', 'update-quantity'])

function removeProduct() {
  // Emit a 'remove' event to the parent
  emit('remove', props.variantId)
}

const displayImg = computed(() => {
  if (!props.img) {
    return '/img/products/product1.png'
  }
  if (props.img.startsWith('http://') || props.img.startsWith('https://') || props.img.startsWith('/')) {
    return props.img
  }
  return `/img/products/${props.img}`
})

watch(() => props.quantity, (value) => {
  qtyAmount.value = value > 0 ? value : 1
}, { immediate: true })
</script>
<template>
  <div class="p-4 border-b border-b-gray">
    <div class="flex gap-4">
      <NuxtImg
        :src="displayImg"
        class="max-w-[80px] sm:max-w-[120px] md:max-w-[140px] lg:max-w-[150px] h-auto rounded-2xl"
      />
      <section class="flex-1">
 
       <div class="flex">
       <div> 
        <h1 class="text-md font-bold">{{ name }}</h1>
        <p class="text-xs md:text-sm">
          Size: <span>{{ size }}</span>
        </p>
        <p class="text-xs md:text-sm">
          Color: <span>{{ color }}</span>
        </p>
      </div>
      <Icon name="ep:delete-filled" class="text-red ml-auto cursor-pointer "  @click="removeProduct"/>
      </div>
      <div class="flex justify-between">
        <h3 class="text-xl font-semibold">${{ price.toFixed(2) }}</h3>

      <div class="flex gap-3 items-center bg-gray rounded-2xl">
        <button
          class="bg-gray hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-l-2xl cursor-pointer"
          @click="decrement" 
        >
          <Icon name="ic:baseline-minus" class="text-base"/>
        </button>
        <p class="mx-2">{{ qtyAmount }}</p>
        <!-- You can replace "1" with a variable to represent the count -->
        <button
          class="bg-gray hover:bg-slate-200 flex items-center justify-center px-5 py-3 rounded-r-2xl cursor-pointer"
          @click="increment"
        >
          <Icon name="ic:round-plus" class="text-base" />
        </button>
      </div>
    </div>
      </section>
    </div>
    
    </div>
</template>

<style scoped></style>
