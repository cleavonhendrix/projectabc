<template>
	<div class="item-holder" v-if="data !== null">
		<div class="item-row-header">
			<label>Title</label>
			<label>Height</label>
			<label>Width</label>
			<label>Status</label>
			<label></label>
		</div>
		<div class="item-row" v-for="item, index in data">
			<div class="title">
				<label><b>{{item.title}}</b></label>
				<label>{{item.height}} px</label>
				<label>{{item.width}} px</label>
				<label>{{item.status}}</label>
				<label>
					<i class="fa fa-chevron-down pull-right action-link" style="padding-right: 10px;" v-if="item.active === false" v-on:click="makeActive(index)"></i>
					<i class="fa fa-chevron-up pull-right action-link" style="padding-right: 10px;" v-if="item.active === true" v-on:click="makeActive(index)"></i>
				</label>
			</div>
			<div class="details" v-if="item.active === true && item.objects !== null">
				<span v-for="itemO, indexO in item.objects">
          <div class="object-holder" v-if="itemO.type === 'text'">
            <span class="object-header">
              <label v-if="itemO.name !== null && itemO.name !== ''">{{itemO.name}}</label>
              <label v-else>Empty ID</label>
            </span>
            <span class="object-content">
              <textarea class="object-input" v-model="itemO.content"></textarea>
            </span>
<!--             <span class="object-action">
              <label>Color</label>
              <input type="text" class="form-control">
            </span> -->
            <span class="object-action">
              <button class="btn btn-primary pull-right" @click="updateText(itemO, index)" style="margin-right: 5px;">Update</button>
            </span>
          </div>
          <div class="object-holder" v-if="itemO.type === 'photo'">
            <span class="object-header">
              <label v-if="itemO.name !== null && itemO.name !== ''">{{itemO.name}}</label>
              <label v-else>Empty ID</label>
            </span>
            <span class="object-content">
              <span class="object-input" style="text-align: center;">
                <img :src="config.BACKEND_URL + itemO.content">
              </span>
            </span>
            <span class="object-action">
              <button class="btn btn-primary"@click="addImage('image' + indexO, indexO, itemO, index)">Upload new photo
                <input type="file" class="form-control" v-bind:id="'image' + indexO" @change="setUpFileUpload($event)" accept="image/*" >
              </button>
              <button class="btn btn-warning pull-right" style="margin-right: 5px;" @click="selectedBrowseImage = indexO">Select from images</button>
            </span>
            <span class="object-browse-images" v-if="selectedBrowseImage === indexO">
              <browse-images :object="itemO" :view="'table-view'" :index="index"></browse-images>
            </span>
          </div>
<!-- 					<div class="input-group" v-if="itemO.type === 'text'">
            <span class="input-group-addon">Object ID</span>
            <input type="text" class="form-control form-control-title" placeholder="*first_name" v-model="itemO.name" disabled>
            <span class="input-group-addon">Content</span>
            <input type="text" class="form-control form-control-content" placeholder="*first_name" v-model="itemO.content">
            <button class="btn btn-primary" style="margin: 5px; width: 150px !important;" @click="updateText(itemO, index)">Update</button>
          </div>
          <div class="input-group" v-if="itemO.type === 'photo'">
            <span class="input-group-addon">Object ID</span>
            <input type="text" class="form-control form-control-title" placeholder="*first_name" v-model="itemO.name" disabled>
            <span class="input-group-addon">Content</span>
            <span class="form-control form-control-content upload-image" @click="addImage('image' + indexO, indexO, itemO, index)">
              <label>Click to add image</label>
              <input type="file" class="form-control" v-bind:id="'image' + indexO" @change="setUpFileUpload($event)" accept="image/*">
            </span>
            <span style="width: 170px;"></span>
          </div>
          <div class="input-group" v-if="itemO.type === 'division' && itemO.attributes.background !== null">
            <span class="input-group-addon">Object ID</span>
            <input type="text" class="form-control form-control-title" placeholder="*first_name" v-model="itemO.name" disabled>
            <span class="input-group-addon">Content</span>
            <input type="text" class="form-control form-control-content" placeholder="*first_name" v-model="itemO.attributes.background">
            <button class="btn btn-primary" style="margin: 5px; width: 150px !important;" @click="updateDivision(itemO, index)">Update</button>
          </div>
          <div class="input-group preview" v-if="itemO.type === 'photo' && itemO.content !== null">
            <img :src="config.BACKEND_URL + itemO.content" height="100%">
          </div>  --> 
				</span>
			</div>
		</div>
	</div>
</template>
<style scoped>
.item-holder{
  width: 100%;
  float: left;
  min-width: 50px;
  overflow-y: hidden;
}
.item-row-header{
	width: 100%;
	float: left;
	height: 50px;
	border-bottom: solid 1px #ddd;
	font-weight: 600;
	color: #555;
}
.item-row-header label{
	line-height: 50px;
	width: 20%;
	margin: 0px;
	float: left;
	padding-left: 10px;
}
.item-row{
	width: 100%;
	float: left;
	min-height: 50px;
	border-bottom: solid 1px #ddd;
	color: #555;
	overflow-y: hidden;
}

.item-row label{
	line-height: 50px;
	width: 20%;
	margin: 0px;
	float: left;
	padding-left: 10px;
}
.item-row i{
	line-height: 50px;
	font-size: 24px;
}

.item-row .title{
	height: 50px;
	float: left;
	width: 100%;
}
.item-row .details{
	min-height: 50px;
	overflow-y: hidden;
	width: 100%;
  margin-bottom: 25px;

}
.details-row{
	width: 75%;
	float: left;
	min-height: 50px;
	margin-right: 25%;
	overflow-y: hidden;
}

.form-control{
  height: 45px !important;
}
.form-control-title{
  width: 30% !important;
  flex: none;
}
.form-control-content{
  width: 40% !important;
  flex: none;
}
.input-group{
  margin-bottom: 10px !important;
}
.input-group-addon{
  width: 15% !important;
  background: #22b173 !important;
  color: #fff !important;
}
.upload-image{
  height: 45px;
}

.upload-image label{
	width: 100%;
	float: left;
}
.upload-image:hover, .upload-image label:hover{
  cursor: pointer;
}
.upload-image input{
  display: none;
}
.preview{
  height: 100px;
  width: 100%;
  float: left;
}
.object-holder{
  width: 24%;
  float: left;
  margin-right: 1%;
  min-height: 100px;
  overflow-y: hidden;
  border: solid 1px #ddd;
  margin-top: 0px;
  margin-bottom: 25px;
}
.object-header{
  height: 30px;
  width: 100%;
  line-height: 30px;
  float: left;
  border-bottom: solid 1px #ddd;
  text-align: center;
}
.object-header label{
  line-height: 30px;
  padding: 0;
  margin: 0;
  width: 100%;
  float: left;
}
.object-content{
  width: 100%;
  float: left;
  height: 100px;
}
.object-content .object-input{
  width: 100%;
  float: left;
  height: 100px;
  max-height: 100px;
  min-height: 100px;
  border: 0;
}

.object-input img{
  max-height: 100px;
  max-width: 100%;
}

.object-action{
  width: 100%;
  float: left;
  border-top: solid 1px #ddd;
  height: 40px;
}
.object-action .btn{
  height: 30px !important;
  padding-top: 5px !important; 
  margin-top: 5px;
  font-size: 10px !important;
  margin-left: 5px;
  font-size: 10px;
  margin-bottom: 5px !important;
}
.object-action .btn input{
  height: 30px;
  line-height: 30px;
  float: left;
  width: 100%;
}
.object-action .btn input{
  display: none;
}
.object-action label{
  width: 50%;
  float: left;
  height: 40px;
  line-height: 40px;
}
.object-action input{
  float: left !important;
  width: 50% !important;
  height: 30px !important;
  margin-top: 5px !important;
}
.object-browse-images{
  width: 200px;
  height: 300px;
  position: absolute;
  border: solid 1px #ddd;
  background: #fff;
  margin-left: -200px;
}
</style>
<script>
import ROUTER from '../../router'
import AUTH from '../../services/auth'
import CONFIG from '../../config.js'
import axios from 'axios'
export default {
  mounted(){
  },
  data(){
    return {
      user: AUTH.user,
      config: CONFIG,
      errorMessage: null,
      file: null,
      fileIndex: null,
      selectedItem: null,
      selectedIndex: null,
      inArrayColor: ['#f', '#ff', '#fff', '#ffff', '#fffff', '#ffffff', 'white'],
      selectedBrowseImage: null
    }
  },
  props: ['data'],
  components: {
    'browse-images': require('modules/editor/BrowseImages.vue')
  },
  methods: {
    redirect(parameter){
      ROUTER.push(parameter)
    },
    makeActive(index){
      this.$parent.makeActive(index)
      this.selectedBrowseImage = null
    },
    addImage(id, index, item, indexInner){
      $('#' + id)[0].click()
      this.fileIndex = index
      this.selectedItem = item
      this.selectedIndex = indexInner
    },
    createFile(file){
      let fileReader = new FileReader()
      fileReader.readAsDataURL(event.target.files[0])
      this.upload()
    },
    setUpFileUpload(event){
      let files = event.target.files || event.dataTransfer.files
      if(!files.length){
        return false
      }else{
        this.file = files[0]
        this.createFile(files[0])
      }
    },
    upload(){
      if(this.selectedItem !== null){
        let formData = new FormData()
        formData.append('file', this.file)
        formData.append('file_url', this.file.name)
        formData.append('account_id', this.user.userID)
        formData.append('template_id', this.selectedItem.template_id)
        $('#loading').css({'display': 'block'})
        axios.post(this.config.BACKEND_URL + '/objects/upload', formData).then(response => {
          if(response.data.data !== null){
            this.updateUrl(response.data.data)
          }
        })
      }
    },
    updateUrl(url){
      let parameter = {
        id: this.selectedItem.id,
        content: url
      }
      this.APIRequest('objects/update', parameter).done(response => {
        $('#loading').css({'display': 'none'})
        if(response.data === true){
          this.$parent.retrieve(this.selectedIndex)
          this.selectedItem = null
          this.selectedIndex = null
        }
      })
    },
    updateText(item, index){
      let parameter = {
        id: item.id,
        content: item.content
      }
      $('#loading').css({'display': 'block'})
      this.APIRequest('objects/update', parameter).done(response => {
        $('#loading').css({'display': 'none'})
        if(response.data === true){
          this.$parent.retrieve(index)
        }
      })
    },
    updateDivision(item, index){
      let parameter = {
        attribute: 'background',
        value: item.attributes.background,
        payload_value: item.id
      }
      $('#loading').css({'display': 'block'})
      this.APIRequest('attributes/update_on_table_view', parameter).done(response => {
        $('#loading').css({'display': 'none'})
        if(response.data === true){
          this.$parent.retrieve(index)
        }
      })
    }
  }
}
</script>
