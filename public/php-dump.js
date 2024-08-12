const BLOCK_START = 0x1;
const BLOCK_END = 0x2;
const REF_BLOCK = 0x6;
const INC_BLOCK = 0x7;
const LINK = 0x5;
const BLOCK_TYPES = 'abfionrsuvkq'.split('').reduce((c, v, i) => {
    c[v.charCodeAt(0)] = ['array', 'boolean', 'float', 'integer', 'object', 'null', 'resource', 'string', 'unknown', 'var', 'key', 'scope'][i];
    return c;
}, {});


class Block{

    type = 'unknown';
    value = undefined;

    getTitle(){
        if(this.title) return this.title;
        if (this.type === 'boolean') return this.value ? 'true' : 'false';
        if (this.type === 'float') return this.value.toFixed(2);
        if (this.type === 'integer') return this.value;
        // if (this.type === 'string') return '"'+this.value+'"';
        if (this.type === 'null') return 'null';
        return '{{'+this.type+'}}';
    }

    getValue(){
        if (this.value) return this.value;
        if (!this.__ref) return undefined;
        this.value = this.__ref();
        if(this.type === 'string' && typeof(this.value) === 'object')this.value = this.value.value;
        return this.value;
    }
}

class Reader {

    offset = 0;
    links;

    constructor(view) {
        this.view = view;
        let depth = 0;
        // Object.getOwnPropertyNames(this.__proto__).map(k=>{
        //     if(typeof this[k] !=='function')return k;
        //     let fn = this[k];
        //     this[k] = (...args)=>{
        //         // console.log('>'+(' '.repeat(depth++)), k, this.offset);
        //         let result = fn.call(this,...args);
        //         depth--;
        //         return result;
        //     }
        // });
    }

    read(){
        let result = [];
        while (this.offset < this.view.byteLength) {
            if (this.current() === BLOCK_START) {
                result.push(this.block());
            } else if (this.current() === REF_BLOCK) {
                //we have refs
                this.offset++;
                let count = this.int();
                let ref_offset = this.offset + (count * 4);
                //we need to look ahead to detect include block size
                let offset = this.offset;
                this.offset = ref_offset;
                let has_includes = this.ch() === INC_BLOCK;
                if (has_includes){
                    //we have includes
                    let inc_count = this.int();
                    ref_offset += inc_count * 8;
                }
                this.offset = offset;
                this.links = [];
                for (let i = 0; i < count; i++) {
                    this.links.push(ref_offset + this.int());
                }
                if (has_includes){
                    if(this.ch() !== REF_BLOCK) throw "unable to read include block";
                    let inc_count = this.int();
                    for (let i = 0; i < inc_count; i++){
                        let index = this.int();
                        this.links[index] = ref_offset + this.int();
                    }
                }
                break;
            } else {
                throw "Unexpected character at offset " + this.offset;
            }
        }

        this.offset = 0;
        return result;
    }

    readLink(id){
        if (this.links[id] === undefined) return undefined;
        if (this.offset !== 0) throw "Unable to readlink, another read operation in progress";
        this.offset = this.links[id];
        let result = this.block();
        this.offset = 0;
        return result;
    }

    current(offset = 0) {
        return this.view.getUint8(this.offset+offset);
    }


    ch() {
        return this.view.getUint8(this.offset++);
    }


    int() {
        let i = this.view.getInt32(this.offset,true);
        this.offset += 4;
        return i;
    }

    string() {
        let len = this.int();
        let str = new TextDecoder().decode(this.view.buffer.slice(this.offset, this.offset+len));
        this.offset += len;
        return str;
    }

    isEnd() {
        return this.current() === BLOCK_END;
    }

    block() {
        let offset = this.offset;
        if (this.ch() !== BLOCK_START) throw "Block not found at offset " + offset;
        let block = new Block();
        let next = this.ch();
        if (next === LINK) {
            let link = this.int();
            block.__ref = this.readLink.bind(this, link);
            block.__link=link;
            next = this.ch();
        }
        block.type = BLOCK_TYPES[next] || 'unknown';
        this[block.type + 'Block'](block);
        if (!this.isEnd()){
             throw "Unable to parse " + block.type + " at offset " + offset;
        }
        this.offset++;
        return block;
    }

    varList(size, value) {
        for (; size > 0; size--) {
            let key = this.block();
            if (!['key', 'var'].includes(key.type)) throw "Unexpected block of type " + key.type + " in variable list";
            value.set(key.index, key.value);
        }
    }


    arrayBlock(block) {
        block.title = this.string();
        block.count = this.int();
        if (this.isEnd()) return;
        block.value = new Map();
        this.varList(block.count, block.value);
    }

    booleanBlock(block) {
        let v = this.ch() ^ 0x30;
        if (![0, 1].includes(v)) throw "Unexpected bool value " + v;
        block.value = !!v;
    }

    floatBlock(block) {
        block.value = this.view.getFloat32(this.offset,true);
        this.offset += 4;
    }

    integerBlock(block) {
        block.value = this.int();
    }

    objectBlock(block) {
        block.title = this.string();
        if (this.isEnd()) return;
        block.fqcn = this.string();
        block.value = new Map();
        let size = this.int();
        this.varList(size, block.value);
    }

    nullBlock(block) {
        block.value = null;
    }

    resourceBlock(block) {
        block.id = this.int();
        block.title = this.string();
    }

    stringBlock(block) {
        block.title = this.string();
        if (block.__ref === undefined) block.value = block.title;
        block.title = '"'+block.title+'"';
    }

    unknownBlock(block) {
        block.title = "Unknown variable type"
    }

    varBlock(block) {
        block.index = this.string();
        block.value = this.block();
    }

    keyBlock(block) {
        block.index = this.int();
        block.value = this.block();
    }

    scopeBlock(block) {
        let o =this.offset;
        block.title = this.string();
        block.value = new Map();
        block.sub = [];
        if (this.current() === BLOCK_START){
            let type =(this.current(1) === LINK) ? this.current(6) : this.current(1);
            if (BLOCK_TYPES[type] === 'array') {
                block.extra = this.block();
            }
        }
        let offset = this.offset;
        let size = this.int();
        try {
            this.varList(size, block.value);
        } catch (error){
            throw {"title":"Unable to parse varList", block, error, size, test:this.test(offset, offset+10)};
        }
        while(this.current()===BLOCK_START){
            let b = this.block();
            if (b.type !== 'scope') throw "Unexpected block of type "+block.type+" in scope";
            block.sub.push(b);
        }
    }

    test(s,e){
        return  new Uint8Array(this.view.buffer.slice(s, e));
    }

}

class PhpDump {

    result;

    constructor(buffer) {
        let view = new DataView(buffer);
        this.reader = new Reader(view);
    }

    read() {
        if (this.result === undefined) this.result = this.reader.read();
        return this.result;
    }

    link(id) {
        return this.reader.readLink(id);
    }


}
