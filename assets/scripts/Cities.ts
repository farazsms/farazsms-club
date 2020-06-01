///<reference path="node_modules/vue/types/umd.d.ts"/>

interface CityDetail {
    city: string;
    city_id: string;
    city_village: string;
    city_village_id: string;
    district: string;
    district_id: string;
    state: string;
    state_id: string;
}

class State {
    id: string;
    name: string;
    cities: City[] = [];

    constructor(result: CityDetail) {
        this.id = result.state_id;
        this.name = result.state;
        this.cities = [new City(result)]
    }

}

class City {
    constructor(result: CityDetail) {
        this.id = result.city_id;
        this.name = result.city;
        this.districts = [new District(result)]
    }

    id: string;
    name: string;
    districts: District[] = [];
}

class District {
    constructor(result: CityDetail) {
        this.id = result.district_id;
        this.name = result.district
        this.city_villages = [new CityVillage(result)]
    }

    id: string;
    name: string;
    city_villages: CityVillage[] = [];
}

class CityVillage {
    constructor(result: CityDetail) {
        this.id = result.city_village_id;
        this.name = result.city_village;
    }

    id?: string;
    name?: string;
}

class SortedCities {
    states: State[];

    constructor(results: CityDetail[]) {
        this.states = []
        const self_states = this.states;
        results.forEach(function (result: CityDetail) {
            if (self_states.length === 0) return self_states.push(new State(result));

            const found_state = self_states.find(_state => _state.id === result.state_id)
            if (found_state === undefined) return self_states.push(new State(result));
            if (found_state.cities.length === 0) return found_state.cities.push(new City(result))

            const found_city = found_state.cities.find(_city => _city.id === result.city_id)
            if (found_city === undefined) return found_state.cities.push(new City(result))
            if (found_city.districts.length === 0) return found_city.districts.push(new District(result))

            const found_district = found_city.districts.find(_district => _district.id === result.district_id)
            if (found_district === undefined) return found_city.districts.push(new District(result))

            if (result.city_village_id) {
                if (found_district.city_villages.length === 0) return found_district.city_villages.push(new CityVillage(result))
                const found_city_village = found_district.city_villages.find(_city_village => _city_village.id === result.city_village_id)
                if (found_city_village === undefined) return found_district.city_villages.push(new CityVillage(result))
            }
        })
    }
}

Vue.component('states-cities', {
    template: `
        <div v-bind:class="{columns:hasSlots}" style="margin-top: 5px">
            <div v-bind:class="{column:hasSlots}">
                <div id="state_container" class="field">
                    <label for="state_id" class="label">
                        استان
                    </label>
                    <div class="select">
                        <select id="state_id" class="mashaghel-select" style="width: 100%" v-model="selected_state">
                            <option v-for="state in this.$props.states.states"
                                    :value="state.id"
                                    :selected="state.id==='08'"
                                    v-text="state.name"
                            ></option>
                        </select>
                    </div>
                </div>
                <div id="city_container" class="field">
                    <label for="city_id" class="label">
                        شهر
                    </label>
                    <div class="select mashaghel-select">
                        <select class="select"
                                id="city_id"
                                style="width: 100%"
                                v-model="selected_city"
                        >
                            <option v-for="city in this.$props.states.states.find(state=>state.id===selected_state).cities"
                                    v-text="city.name" :value="city.id" :selected="city.id==='06'">
                            </option>
                        </select>
                    </div>
                </div>
                <div id="district_container" class="field">
                    <label for="district_id" class="label">
                        منطقه
                    </label>
                    <div class="select">
                        <select id="district_id"
                                class="mashaghel-select select"
                                style="width: 100%"
                                v-model="selected_district"
                        >
                            <option
                                    v-for="district in 
                                this.$props.states.states.find(state=>state.id===selected_state).cities
                                .find(city=>city.id===selected_city).districts"
                                    v-text="district.name" :value="district.id" :selected="district.id==='01'">
                            </option>
                        </select>
                    </div>
                </div>
                <div id="city_villages_container" v-if="with_city_village && get_city_villages() !== false"
                     class="field">
                    <label for="city_villages_id" class="label">
                        حوزه
                    </label>
                    <div class="select">

                        <select id="city_villages_id"
                                class="mashaghel-select select"
                                style="width: 100%"
                                v-model="selected_city_village"
                        >
                            <option :value="null" selected> مرکزی</option>
                            <option
                                    v-for="c in 
                                get_city_villages()"
                                    v-text="c.name"
                                    :value="c.id"
                            >
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <div v-bind:class="{column:hasSlots}">
                <slot></slot>
            </div>
           
        </div>
    
    `,
    props: {
        'states': {
            type: SortedCities,
            default: [],

        }, 'with_city_village': {
            type: Boolean,
            default: true
        }
    },
    data: function () {
        return {
            selected_state: "08",
            selected_city: "06",
            selected_district: "01",
            selected_city_village: null,
            // states_data: this.$props
        }
    },
    methods: {
        get_city_villages() {
            const districts = this.$props.states.states.find(state => state.id === this.$data.selected_state)
                .cities.find(city => city.id === this.$data.selected_city).districts
            if (districts === undefined || districts.length === 0) return false
            let city_villages = districts.find(district => district.id === this.$data.selected_district).city_villages
            if (city_villages.length === 0) return false
            city_villages = city_villages.filter(c => c.id !== null)
            // city_villages.forEach(c=>console.log(c.id,c.name))
            return city_villages
        },

    },
    computed: {
        hasSlots() {
            return this.$slots.default;
        }
    },
    mounted() {
        console.log(this.$slots)

        this.$emit('districtData', this.$data)
    },
    updated() {
        this.$emit('districtData', this.$data)
    },

})
Vue.component('add-avenue',
    {
        template: `
            <div>
                <div class="columns">
                    <div class="column">
                        <states-cities @districtData="districtChanged" :with_city_village="true"
                                       :states="states"></states-cities>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label class="label">
                                نام خیابان
                            </label>
                            <input class="control" type="text" placeholder="نام خیابان" required
                                   v-model="name" style="text-align: right">
                        </div>
                        <div class="field is-grouped">
                            <div class="control">
                                <button class="button is-link" type="submit" @click="saveAvenue">Submit</button>
                            </div>
                            <div class="control">
                                <button class="button is-link is-light">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <div class="modal" :class="{'is-active':saved}">
                        <div class="modal-background"></div>
                        <div class="modal-card">
                            <header class="modal-card-head">
                                <p class="modal-card-title">ذخیره شد</p>
                                <button class="delete" aria-label="close"
                                        @click="_=>{$data.saved=false;$data.name=null}"></button>
                            </header>
                            <section class="modal-card-body">
                                <h1>خیابان
                                    {{name}}
                                    ذخیره شد
                                </h1>
                            </section>
                            <footer class="modal-card-foot">

                            </footer>
                        </div>
                    </div>
                </div>
                <div style="display: flex;flex-wrap: wrap" v-if="saved_avenues.length>0">
                    <div v-for="avenue in listAvenues()">
                        <div class="card">
                            <div class="card-content">
                                <p class="title" v-text="avenue.name"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        methods: {
            districtChanged(data) {
                this.states_incoming_data = data;
                this.fetch_avenues()
            },
            async fetch_avenues() {
                // @ts-ignore
                const res = await fetch(ajaxurl, {
                    body: new URLSearchParams({
                        action: 'getDistrictsAvenues',
                        ...this.$data.states_incoming_data,
                        ...{name: this.$data.name},
                    }).toString(), method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",}
                })
                const j = await res.json();
                this.$data.saved_avenues = j;
            },
            async saveAvenue() {
                // @ts-ignore
                const res = await fetch(ajaxurl, {
                    body: new URLSearchParams({
                        action: 'setDistrictsAvenues',
                        ...this.$data.states_incoming_data,
                        ...{name: this.$data.name},
                    }).toString(), method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",}
                })
                const j = await res.json()
                this.$data.saved = true
                await this.fetch_avenues()
            },
            listAvenues() {
                if (this.$data.name)
                    return this.$data.saved_avenues.filter(av => av.name.includes(this.$data.name));
                return this.$data.saved_avenues;
            }
        },
        data() {
            return {
                states_incoming_data: null,
                name: null,
                saved: false,
                saved_avenues: []
            }
        },
        props: ['states']
    }
)
Vue.component('select-avenues', {
    template: `
        <div>
            <div>
                <states-cities :with_city_village="true" :states="states"
                               @districtData="districtChanged"></states-cities>
            </div>
            <div  v-if="$data.saved_avenues.length>0">
                <div class="field">
                    <label for="main_avenue" class="label">خیابان اصلی</label>
                    <div class="select">
                        <select name="main_avenue mashaghel-select" id="main_avenue">
                            <option v-for="avenue in saved_avenues" value="avenue.id" v-text="avenue.name"></option>
                        </select>
                    </div>
                    <input style="margin:5px" type="text" class="input mashaghel-select" name="avenue2" placeholder="خیابان فرعی">
                    <input style="margin:5px" type="number" class="input mashaghel-select" name="postal_code" placeholder="کدپسنی">
                    <input style="margin:5px" type="number" class="input mashaghel-select" name="postal_code" placeholder="همراه">
                    <input style="margin:5px" type="email" class="input mashaghel-select" name="postal_code" placeholder="آدرس ایمیل">
                    <input style="margin:5px" type="url" class="input mashaghel-select" name="postal_code" placeholder="آدرس سایت">

                </div>
            </div>
        </div>
    `,
    methods: {
        districtChanged(data) {
            this.states_incoming_data = data;
            this.fetch_avenues()
        },
        async fetch_avenues() {
            // @ts-ignore
            const res = await fetch(ajaxurl, {
                body: new URLSearchParams({
                    action: 'getDistrictsAvenues',
                    ...this.$data.states_incoming_data,
                    ...{name: this.$data.name},
                }).toString(), method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",}
            })
            const j = await res.json();
            this.$data.saved_avenues = j;
        },

    },
    mounted(){
        // jQuery('.mashaghel-select').select2();
    },
    data() {
        return {
            states_incoming_data: null,
            name: null,
            saved: false,
            saved_avenues: []
        }
    },
    props: ['states']

})

async function get_states_cities() {
// @ts-ignore
    const response = await fetch(ajaxurl, {
        body: 'action=getStatesCities', method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",}
    })
    return await response.json()
}

async function setStatesInInput(element_string = '#address_selector') {
    let el_container = document.querySelector(element_string);
    if (el_container !== undefined && el_container !== null) {
        const raw_results = await get_states_cities();
        const states = new SortedCities(raw_results);
        new Vue({
            el: el_container,
            template: element_string.includes('avenue') ? `<add-avenue :states="states"></add-avenue>`
                : `<select-avenues :states="states"></select-avenues>`,
            data: {states: states},

        })
    }
}

jQuery(async _ => {
    await setStatesInInput()
    await setStatesInInput('#add_avenue')
})
