<template>
  <div id="app">
    <div v-bind:style="(globalVariables.showModal) ? 'overflow-y:hidden; height:'+deviceHeight+'px!important': ''">
      <div v-if="tokenData.token !== null && parseInt(user.userID) > 0">
       <system-header></system-header>
       <system-sidebar></system-sidebar>
       <support-messenger></support-messenger>
      </div>
      <div v-else>
        <login-header></login-header>
        <system-content></system-content>
        <landing-footer></landing-footer>
      </div>
    </div>
    <!-- <system-footer></system-footer> -->
    <system-loading></system-loading>
    <privacy-policy></privacy-policy>
    <terms-and-conditions></terms-and-conditions>
    <guide></guide>
  </div>
</template>
<script>
import ROUTER from './router'
import AUTH from './services/auth'
import global from './helpers/global'
export default {
  name: 'app',
  mounted(){
    // this.validate()
    console.log(this.tokenData.token)
  },
  created(){
    // this.validate()
  },
  data(){
    return {
      user: AUTH.user,
      tokenData: AUTH.tokenData,
      currentRoute: ROUTER.currentRoute.name,
      deviceHeight: document.documentElement.clientHeight,
      appGlobal: {
        showModal: false
      },
      globalVariables: global
    }
  },
  methods: {
    logOut(){
      AUTH.deaunthenticate()
    },
    validate(){
      if(this.tokenData.verifyingToken === false && this.tokenData.token !== null){
        ROUTER.push('/dashboard')
      }else{
        ROUTER.push('/login')
      }
    }
  },
  components: {
    'login-header': () => import('modules/home/Landing/Header.vue'),
    'system-header': () => import('modules/frame/Header.vue'),
    'system-sidebar': () => import('modules/frame/Sidebar.vue'),
    'system-content': () => import('modules/frame/Content.vue'),
    'system-footer': () => import('modules/frame/Footer.vue'),
    'system-loading': () => import('components/loader/Loading.vue'),
    'landing-footer': () => import('modules/home/Landing/Footer.vue'),
    'privacy-policy': () => import('modules/docs/PrivacyPolicy.vue'),
    'terms-and-conditions': () => import('modules/docs/TermsAndConditions.vue'),
    'guide': () => import('modules/guide/Guide.vue'),
    'support-messenger': () => import('modules/support/Support.vue')
  }
}
</script>

<style>
.half-width{
  width: 50%
}
.push-half-right{
  margin-left: 50%
}
.nav a{
  font-size: 15px
}
.dropdown-menu li a{
  padding: 10px;
}
.container {
   min-height:100%;
   position:relative;
}
</style>
