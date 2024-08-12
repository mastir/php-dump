
const ReactDOM = window.ReactDOM;
const React = window.React;
const hljs = window.hljs;

const Code = function({code, line, code_line}){
    const ref = React.useRef(null);
    if(code.substring(code.length - 1)==="\n") {
        code = code.substring(0, code.length - 1);
    }
    let result = hljs.highlight(code,{language:'php', ignoreIllegals:true});
    // return <pre><code dangerouslySetInnerHTML={{__html:result.value}}/></pre>;
    return <pre><code className="code">
        <ol start={code_line+1}>
            {result.value.split("\n").map((html,i)=><li className={(code_line+i+1)===line?'current':''} dangerouslySetInnerHTML={{__html:html}}></li>)}
        </ol>
    </code></pre>;
}

const ArrayValue = function({item,close=()=>{}}){
    if (!item) return 'UNDEFINED';
    const array = Array.from(item.getValue().entries());
    return <div className="variable__value array open">
        <span className="accordion" onClick={close}>Array</span>
        <div className="array__items">
            {array.map(([key,value])=><Variable name={key} value={value} />)}
        </div>
    </div>;
}


const ObjectValue = function({item,close=()=>{}}){
    const array = Array.from(item.value.entries());
    return <div className="variable__value object open">
        <span className="accordion" onClick={close}>{item.fqcn}</span>
        <div className="object__items">
            {array.map(([key,value])=><Variable name={key} value={value} />)}
        </div>
    </div>;
}


const ScalarValue = function({item}){
    return item;
}

const VariableValue = function ({item}){
    const ValueType = item.type === 'array' ? ArrayValue : (item.type === 'object') ? ObjectValue : ScalarValue;
    const [open, setOpen] = React.useState(false);
    const is_extendable =
        (
            (['array', 'object', 'string'].indexOf(item.type) !== -1)
            && item.__ref !== undefined
            && item.getValue() !== undefined
        ) || item.type === 'float';
    return <span className={"variable__type type__"+item.type}>

            {open
                ? <ValueType item={item.getValue()} close={()=>setOpen(false)}/>
                : is_extendable
                    ? <span className="variable__value accordion" onClick={()=>setOpen(true)}>{item.getTitle()}</span>
                    : <span className="variable__value">{item.getTitle()}</span>
            }
        </span>
}


const Variable = function({name,value, separator: separator = false}){
    return <div className={"variable"+(open?' open':'')}>
        <span
            className={"variable__name"}
        >{name}</span>
        {separator && <span className="variable__seporator">{separator}</span>}
        <span
            className={"variable__title"}
        >
            <VariableValue item={value} />
        </span>
    </div>
}


const Scope = function({item, level=0}){
    const vars = item.getValue();
    const [open, setOpen] = React.useState(false);
    let title = item.getTitle();
    const extra = {};
    if (item.extra){
        let array_block = item.extra.getValue();
        for(let [key,value] of array_block.value){
            extra[key] = value.getValue();
        }
    }
    return <div className={"scope"+(open?' open':'')}>
        <h4 className={"accordion level-"+level} onClick={()=>setOpen(o=>!o)}>
            {title}
        </h4>
        {open && <div className="vars">
            {vars.keys().map(
                key => <Variable key={key} name={'$'+key} value={vars.get(key)} />
            )}
        </div>}
        {open && extra && extra.code && <div className="code-block">
            <Code {...extra} />
        </div>}
        <div className="scope--sub">
        {item.sub.map(
            (sub,i)=><Scope key={i} item={sub} level={level+1}/>
        )}
        </div>
    </div>
};


window.renderReactDump = function(root, dump){
    // ReactDOM.render(React.createElement(React.Fragment,[],...dump.map(s=>React.createElement(Scope, s))),root);
    ReactDOM.render(
        <div className="php-dump">
            {dump.map(
                s=><Scope item={s} />
            )}
        </div>,root);
}

