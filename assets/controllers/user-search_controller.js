import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {url: String};

    static targets = ["search", "result", "select", "liste"];

    members = {};

    async search() {
        const response = await fetch(this.urlValue+'/'+this.searchTarget.value+'?xhr=1')
        this.resultTarget.innerHTML = await response.text();

        this.resultTarget.classList.remove('visually-hidden');
    }

    select(e) {
        this.members[e.target.dataset.id] = {name: e.target.dataset.name};

        this.listeTarget.innerHTML = '';
        this.selectTarget.innerHTML = '';
        let ul = document.createElement('ul');
        ul.classList.add('d-inline', 'list-group');
        Object.keys(this.members).forEach(id => {
            // Ajout des éléments dans le select multiple
            let option = document.createElement('option');
            option.value = id;
            option.selected = 1;
            option.innerText = this.members[id].name;
            this.selectTarget.append(option);

            let li = document.createElement('li');
            li.classList.add('list-group-item');
            li.innerHTML = `${this.members[id].name}`;

            ul.append(li);
        });
        this.listeTarget.append(ul);

        this.resultTarget.classList.add('visually-hidden');
    }
}