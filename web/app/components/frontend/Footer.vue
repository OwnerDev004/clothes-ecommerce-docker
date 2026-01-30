<template>
    <div>
   <!-- stay comuties -->
   <section class="pt-20 px-5 desktop:container bottom-0">
      <div class="flex justify-between flex-col desktop:flex-row bg-black p-10 rounded-2xl">
           <h1 class="w-full text-left mb-3 desktop:w-1/2 text-white font-Poppins text-2xl md:text-3xl desktop:text-4xl">STAY UPTO DATE ABOUT OUR LATEST OFFERS</h1>
           <div class=" flex flex-col gap-3">
            <div class="relative ml-auto w-full desktop:w-auto">
    <!-- Search Icon -->
              <Icon name="mdi:email" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-200 text-lg" />
            <input type="email" class="rounded-[62px] bg-gray px-16 outline-none w-full py-2 text-sm desktop:text-lg"
            placeholder="Enter your email">
            </div>
            <button class="ml-auto text-black bg-white rounded-2xl px-14 py-2 w-full text-sm desktop:w-auto desktop:text-lg">Subscribe t Newsletter</button>
           </div>
      </div>
    </section>
    <section class="pt-20 bg-gray ">
        <div class="px-5 desktop:container">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5  items-center text-justify border-b pb-10">
               <div>
                <h1 class="font-Poppins text-4xl font-bold">SHOP.CO</h1>
                <p>We have clothes that suits your style and which you’re proud to wear. From women to men.</p>
                 <ul class="flex gap-2">
                    <li><Icon name="mdi:twitter" /></li>
                    <li><Icon name="mdi:facebook" /></li>
                    <li><Icon name="mdi:instagram" /></li>
                    <li><Icon name="mdi:github" /></li>
                 </ul>
               </div>
               <div>
                <h1 class="text-lg font-Poppins font-bold">COMPANY</h1>
                <ul>
                    <li>About</li>
                    <li>Feature</li>
                    <li>Works</li>
                    <li>Career</li>
                </ul>
               </div>
               <div>
                <h1 class="text-lg font-Poppins font-bold">HELP</h1>
                <ul>
                    <li>Customer Support</li>
                    <li>Delivery Details</li>
                    <li>Terms & Conditions</li>
                    <li>Privacy Policy</li>
                </ul>
               </div>
               <div>
                <h1 class="text-lg font-Poppins font-bold">FAQ</h1>
                <ul>
                    <li>Account</li>
                    <li>Manage Deliveries</li>
                    <li>Orders</li>
                    <li>Payments</li>
                </ul>
               </div>
               <div>
                <h1 class="text-lg font-Poppins font-bold">Resources</h1>
                <ul>
                    <li>Free eBooks</li>
                    <li>Development Tutorial</li>
                    <li>How to - Blog</li>
                    <li>Youtube Playlist</li>
                </ul>
               </div>
            </div>
            <div class="flex justify-between pb-20 pt-10">
                <p>Shop.co © 2000-2023, All Rights Reserved</p>
                <ul class="flex gap-2">
                    <li class="w-[50px] bg-white border-slate-400 rounded-md items-center flex justify-center py-3"><Icon name="logos:visa" class="text-[14px]"/></li>
                    <li class="w-[50px] bg-white border-slate-400 rounded-md items-center flex justify-center py-3"><Icon name="logos:mastercard" class="text-[14px]"/></li>
                    <li class="w-[50px] bg-white border-slate-400 rounded-md items-center flex justify-center py-3"><Icon name="logos:paypal" class="text-[14px]"/></li>
                    <li class="w-[50px] bg-white border-slate-400 rounded-md items-center flex justify-center py-3"><Icon name="logos:google-pay" class="text-[14px]"/></li>
                 </ul>
            </div>
        </div>
    </section>
    </div>
</template>

<script setup lang="ts">

</script>

<style scoped>

</style>